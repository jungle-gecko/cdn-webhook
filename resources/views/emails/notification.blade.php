<div>{{ $result ? 'A new release has been successfully published.' : 'A new release could not be published.' }}</div>
<hr/>
<div>Repository: <strong>{{ $repository }}</strong></div>
<div>Release: <strong>{{ $tag }}</strong></div>
<div>URL: <strong>{{ url($repository) }}</strong></div>
