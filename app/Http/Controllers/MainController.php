<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mail;
use Symfony\Component\Process\Process;

class MainController extends Controller
{
    public function index(Request $request)
    {
        $path = public_path($request->path());
        
        if (!is_dir($path)) {
            abort(404);
        }
        else {
            $list = collect(glob($path . '/*'));
            
            if ($path == public_path('/')) {
                $list = $list->filter(function ($value, $key) {
                    $name = pathinfo($value, PATHINFO_BASENAME);
                    return is_dir($value) && $name != 'css' && $name != 'png';
                });
            }
            
            $list = $list->map(function ($item, $key) use ($request) {
                $file = [
                    'date' => date('d/m/Y H:i', filemtime($item)),
                    'extension' => pathinfo($item, PATHINFO_EXTENSION),
                    'link' => ($request->path() == '/' ? '' : $request->path()) . '/' . pathinfo($item, PATHINFO_BASENAME),
                    'name' => pathinfo($item, PATHINFO_BASENAME),
                    'path' => $item,
                    'type' => is_dir($item) ? 'folder' : 'file',
                ];
                if ($file['type'] == 'folder') {
                    $file['icon'] = 'folder.png';
                }
                else if ($file['extension'] == null) {
                    $file['icon'] = 'file.png';
                }
                else if (file_exists(public_path('png/' . $file['extension'] . '.png'))) {
                    $file['icon'] = $file['extension'] . '.png';
                }
                else {
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
                        'link' => $carry->last()['link'] . '/' . $item,
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
	    if ($request->header('X-GitHub-Event') == 'ping')
	    {
	        return 'OK';
	    }
	    
	    // Else, we ensure this is a Release event
	    else if (!$request->header('X-GitHub-Event') == 'release')
	    {
	        abort(500);
	    }
	    
	    $result = false;
	    
		$payload = json_decode($request->getContent());
		
		$authorized_repositories = explode(',', env('AUTHORIZED_REPOSITORIES', ''));

		if ($payload->action == "published" && $repository = in_array($payload->repository->owner->login, $authorized_repositories))
		{
			$path = env('PUBLISH_PATH', public_path());
			
			$repository = $payload->repository->full_name;
			
			$tag = $payload->release->tag_name;
			if (starts_with($tag, 'v') or starts_with($tag, 'V'))
			{
				$tag = substr($tag, 1);
			}
			
			$folder = $payload->repository->name . '/' . $tag;
			
			$process = new Process('cd ' . $path . ' && composer create-project ' . $repository . '=' . $tag . ' ' . $folder);
			$process->run();
			if ($process->isSuccessful())
			{
                $result = true;
			}
		}
		
		// We send a notification by mail
		Mail::send('emails.notification', compact('result', 'repository', 'tag', 'folder'), function($message) use ($result)
		{
		    $message
    		    ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
    		    ->to(env('MAIL_TO'))
    		    ->subject('CDN publishing ' . $result ? 'successful' : 'failed');
		});
		
		if (!$result)
		{
		    abort(500);
		}
		else 
		{
		    return 'OK';
		}
	}
}
