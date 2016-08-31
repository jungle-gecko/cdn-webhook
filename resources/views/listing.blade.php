<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>CDN Webhook</title>

    <!-- Styles -->
	<link media="all" type="text/css" rel="stylesheet" href="{{ url('css/bootstrap/css/bootstrap.min.css') }}">
    <link media="all" type="text/css" rel="stylesheet" href="{{ url('css/styles.css') }}">
    
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    
</head>
<body id="app-layout">
    <nav class="navbar navbar-default navbar-static-top">
        <div class="container text-center">
            <div class="navbar-header">

                <!-- Branding Image -->
                <a class="navbar-brand" href="{{ url('/') }}">
                    CDN Webhook
                </a>
            </div>

        </div>
    </nav>

    <div class="container">
    	<div class="row">
    		<div class="col-md12">
    			<ol class="breadcrumb">
    				@foreach ($breadcrumbs as $breadcrumb)
    					<li>{{ link_to($breadcrumb['link'], $breadcrumb['title']) }}</li>
    				@endforeach
                </ol>
    		</div>
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-success" id="websites_panel">
                    <div class="panel-heading">
                    	Browse resources
                    </div>
                    <table class="table">
                    	<tbody>
    						@forelse ($list as $group)
    							@foreach ($group as $item)
        							<tr>
    									<td class="col-md-2 text-center"><img alt="{{ $item['extension'] }}" class="icon" src="{{ url('png/' . $item['icon']) }}"></td>
        								<td class="col-md-5">{{ link_to($item['link'], $item['name']) }}</td>
        								<td class="col-md-5">{{ $item['date']  }}</td>
        							</tr>
        						@endforeach
    						@empty
    							<tr>
    								<td>
    									No resource available.
    								</td>
    							</tr>
    						@endforelse 
						</tbody>
					</table>
                </div>
            </div>
        </div>
        <div class="text-center">
	        <small class="text-muted">Icons from <a href="http://www.flaticon.com">www.flaticon.com</a> by <a href="http://www.flaticon.com/authors/madebyoliver">Madebyoliver</a></small>
        </div>
    </div>
    
	<script src="http://sitarium.localhost/sitarium/jquery/jquery.min.js?1471435146"></script>
	<script src="http://sitarium.localhost/sitarium/bootstrap/js/bootstrap.min.js?1471435146"></script>

</body>
</html>