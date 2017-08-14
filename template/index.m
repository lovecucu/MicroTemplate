<html>
	<meta http-eqiv="content_type" charset="utf8">
	<head>
		<title>测试</title>
	</head>

	<body>
		{foreach $names as $v=>$aa}
			<p>{$aa}</p>
		{/foreach}

		{if $name == 'test'}
			<p>{$name}</p>
		{else if $name =='test2'}
			<p>{$name}</p>
		{else}
			<p>{$name}</p>
		{/if}
	</body>
</html>
