<?php

$bearerToken = '';

if (isset($_POST["tracking"])){
	$trackingNum = trim($_POST["tracking"], " ");
} else {
	$trackingNum = "";
}
?>

<form action="getjson.php" method="post">
<input type="text" name="tracking" placeholder="tracking number">
<input type="submit" value="Get Package">
</form>

<?php
	if($trackingNum) {
		echo "Tracking info for: " . $trackingNum . '<br /><br />';
		
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://api.trackinghive.com/trackings?pageId=1&limit=20");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		  "Content-Type: application/json",
		  "Authorization: Bearer " . $bearerToken
		));

		$response = curl_exec($ch);
		curl_close($ch);
		
		$json = json_decode($response, false);
		
		foreach ($json->data as $mydata) {
			if ($mydata->tracking_number == $trackingNum) {
				$packageid = $mydata->_id;
				
				$ch = curl_init();

				curl_setopt($ch, CURLOPT_URL, "https://api.trackinghive.com/trackings/". $packageid);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_HEADER, FALSE);

				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				  "Content-Type: application/json",
				  "Authorization: Bearer " . $bearerToken
				));

				$response = curl_exec($ch);
				curl_close($ch);
				

				$data = $response;

				//Declare the custom function for formatting
				function pretty_print($json_data)
				{

					//Initialize variable for adding space
					$space = 0;
					$flag = false;

					//Using <pre> tag to format alignment and font
					echo "<pre>";

					//loop for iterating the full json data
					for($counter=0; $counter<strlen($json_data); $counter++)
					{

						//Checking ending second and third brackets
						if ( $json_data[$counter] == '}' || $json_data[$counter] == ']' )
						{
							$space--;
							echo "\n";
							echo str_repeat(' ', ($space*2));
						}
				 

						//Checking for double quote(“) and comma (,)
						if ( $json_data[$counter] == '"' && ($json_data[$counter-1] == ',' ||
						 $json_data[$counter-2] == ',') )
						{
							echo "\n";
							echo str_repeat(' ', ($space*2));
						}
						
						if ( $json_data[$counter] == '"' && !$flag )
						{
							if ( $json_data[$counter-1] == ':' || $json_data[$counter-2] == ':' )

							//Add formatting for question and answer
							echo '<span style="color:blue;font-weight:bold">';
							else

							//Add formatting for answer options
							echo '<span style="color:red;">';
						}
						echo $json_data[$counter];
						//Checking conditions for adding closing span tag
						if ( $json_data[$counter] == '"' && $flag )
						echo '</span>';
						if ( $json_data[$counter] == '"' )
						$flag = !$flag;

						//Checking starting second and third brackets
						if ( $json_data[$counter] == '{' || $json_data[$counter] == '[' )
						{
							$space++;
							echo "\n";
							echo str_repeat(' ', ($space*2));
						}
					}
					echo "</pre>";
				}
				
				//call custom function for formatting json data
				echo pretty_print($data);
				
				//print unfiltered json
				print_r($data);
			}
		}
		
		
	}

?>