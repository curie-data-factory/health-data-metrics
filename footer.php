<footer>
	<br/>
	<p>
			Health Data Metrics - <?php 
		echo(date('Y'));

        # ouverture du json
		$json_version = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/version/version.json');
		$json_version_data = json_decode($json_version);

		echo(" - Version : ".$json_version_data->version);                
		?>
	</p>
</footer>
</body>
</html>
