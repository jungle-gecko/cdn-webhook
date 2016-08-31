<?php

namespace App\Http\Controllers;

use File;
use Illuminate\Http\Request;
use Mail;
use ZipArchive;

class MainController extends Controller
{
    public function index(Request $request)
    {
        $path = public_path($request->path());

        if (!is_dir($path)) {
            abort(404);
        } else {
            $list = collect(glob($path.'/*'));

            if ($path == public_path('/')) {
                $list = $list->filter(function ($value, $key) {
                    $name = pathinfo($value, PATHINFO_BASENAME);

                    return is_dir($value) && $name != 'css' && $name != 'png';
                });
            }

            $list = $list->map(function ($item, $key) use ($request) {
                $file = [
                    'date'      => date('d/m/Y H:i', filemtime($item)),
                    'extension' => pathinfo($item, PATHINFO_EXTENSION),
                    'link'      => ($request->path() == '/' ? '' : $request->path()).'/'.pathinfo($item, PATHINFO_BASENAME),
                    'name'      => pathinfo($item, PATHINFO_BASENAME),
                    'path'      => $item,
                    'type'      => is_dir($item) ? 'folder' : 'file',
                ];
                if ($file['type'] == 'folder') {
                    $file['icon'] = 'folder.png';
                } elseif ($file['extension'] == null) {
                    $file['icon'] = 'file.png';
                } elseif (file_exists(public_path('png/'.$file['extension'].'.png'))) {
                    $file['icon'] = $file['extension'].'.png';
                } else {
                    $file['icon'] = 'file.png';
                }

                return $file;
            })
            ->keyBy('name')
            ->groupBy('type')
            ->sortBy(function ($item, $key) {
                return $key == 'folder' ? 0 : 1;
            });

            $breadcrumbs = collect(explode('/', $request->path()))
            ->reduce(function ($carry, $item) {
                if ($item != '') {
                    return $carry->push([
                        'title' => $item,
                        'link'  => $carry->last()['link'].'/'.$item,
                    ]);
                }

                return $carry;
            }, collect([['title' => 'Home', 'link' => url('/')]]));

            return view('listing')->with(compact('list', 'breadcrumbs'));
        }
    }

    public function publish(Request $request)
    {
        // In case of Ping event (setting up the webhook), we just say OK
        if ($request->header('X-GitHub-Event') == 'ping') {
            return 'OK';
        }

        // Else, we ensure this is a Release event
        if ($request->header('X-GitHub-Event') != 'release') {
            abort(500);
        }

        $json = $request->input('payload');
        $payload = json_decode($json, false);

        // We ensure the Secret hash is valid
        list($algo, $github_hash) = explode('=', $request->header('X-Hub-Signature'), 2);
        $payload_hash = hash_hmac($algo, $request->getContent(), env('SECRET'));
        if (!hash_equals($github_hash, $payload_hash)) {
            abort(401);
        }

        $authorized_repositories = explode(',', env('AUTHORIZED_REPOSITORIES', ''));

        // We ensure the Repository is authorized
        if (!in_array($payload->repository->owner->login, $authorized_repositories)) {
            abort(401);
        }

        $result = false;

        $repository = $payload->repository->full_name;
        if (!is_dir($repository)) {
            mkdir($repository, 0777, true);
        }

        $tag = $payload->release->tag_name;
        if (starts_with($tag, 'v') or starts_with($tag, 'V')) {
            $tag = substr($tag, 1);
        }
        if (is_dir($repository.'/'.$tag)) {
            File::deleteDirectory($repository.'/'.$tag);
        }

        $path = env('PUBLISH_PATH', public_path($repository));

        $client = new \GuzzleHttp\Client([
            'verify' => app_path('cacert.pem'),
        ]);
        $client->get($payload->release->zipball_url, ['save_to' => $path.'/'.$tag.'.zip']);

        $zip = new ZipArchive();
        $zip->open($path.'/'.$tag.'.zip');
        $zip_folder = $zip->getNameIndex(0);
        $result = $zip->extractTo($path);
        $zip->close();

        if ($result) {
            $result = rename($path.'/'.$zip_folder, $path.'/'.$tag);
        }

        // We send a notification by mail
        Mail::send('emails.notification', compact('result', 'repository', 'tag'), function ($message) use ($result) {
            $message
                ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
                ->to(env('MAIL_TO'))
                ->subject('CDN publishing '.$result ? 'successful' : 'failed');
        });

        if (!$result) {
            abort(500);
        } else {
            return 'OK';
        }
    }
}
