<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<!-- CSRF Token -->
		<meta name="csrf-token" content="{{ csrf_token() }}">

		<title>@yield('titulo')</title>
	</head>
	<body>
		<div class="row justify-content-center">
			
			<div class="col-sm-12">
				<img src="{{asset('img/ludke-red.png')}}"  style="width: 100px; float:left; position:absolute">
				<h1 style="text-align:center; top:0">@yield('titulo')</h1>
				<h3 style="text-align:center;">Emitido em @yield('date')</h3>
			</div>
			
		</div>
		
		@yield('content')
	</body>


</html>
