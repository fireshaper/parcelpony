<html>
<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<link href="styles.css" rel="stylesheet" type="text/css"/>
<link href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" rel="stylesheet" type="text/css" />

<link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
<link rel="manifest" href="site.webmanifest">

<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="theme-color" content="#94b5eb"/>
<title><?php echo isset($pageTitle) ? $pageTitle : "ParcelPony"; ?></title>
</head>

<body onload = "Javascript:AutoRefresh(1800000);">
<?php

$config = include('config.php');

//----- Global Stuff -----//

/*Get an API Token from my.trackinghive.com and put it here */
$bearerToken = $config["BearerToken"];

$version = "0.5.2";

//echo "Setting alertMessage to ' '<br />";
$alertMessage = "";

$comment = "No title";

$now = new DateTime();
$todayTS = date_timestamp_get($now);
$today = $now->format('m/d/Y');


//----- Functions -----//



//-- PHP Stuff --//

set_error_handler('exceptions_error_handler');

function exceptions_error_handler($severity, $message, $filename, $lineno) {
  if (error_reporting() == 0) {
    return;
  }
  if (error_reporting() & $severity) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
  }
}

function deleteParcel($_id, $bearerToken){
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "https://api.trackinghive.com/trackings/". $_id);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);

	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	  "Content-Type: application/json",
	  "Authorization: Bearer " . $bearerToken
	));

	$response = curl_exec($ch);
	curl_close($ch);

	//var_dump($response);

	$json = json_decode($response, true);
	if ($json['meta']['code'] == 200) {
		//echo '<font color="red">Package removed!</font><br /><br />';
		$alertMessage = "Removed";
		//echo $alertMessage;
	}

	return $alertMessage;
}

function addSubscription ($_id, $bearerToken) {
	if (!isset($config["Subscription"]) || !isset($config["Subscription"]["Enabled"]) || !$config["Subscription"]["Enabled"]) {
		return;
	}
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "https://api.trackinghive.com/webhook/subscription");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);

	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
	  "endpoint_url" => $config["Subscription"]["Endpoint"],
	  "notify_if_inactive" => true,
	  "email_alerts" => $config["Subscription"]["Emails"],
	  "active" => true,
	  "id" => $_id
	]));

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	  "Content-Type: application/json",
	  "Authorization: Bearer " . $bearerToken
	));

	$response = curl_exec($ch);
	curl_close($ch);

	var_dump($response);
}

function getCustomFields($rawFields) {
	if(is_string($rawFields)) {
		$fields = explode(':', $rawFields);
		return (object)[ $fields[0] => $fields[1] ];
	} else if (is_object($rawFields)){
		return $rawFields;
	}
    return (object)[];
}

//----- Check for _POST variables -----//

/* Check if a parcel was deleted */
if(isset($_POST['action']) && isset($_POST['id'])) {
	if ($_POST['action'] == 'Delete') {
		$alertMessage = deleteParcel($_POST['id'], $bearerToken);
	}
}

/* Check if a parcel was added on page load */
if (isset($_POST["submit"]) && isset($_POST["tracking"])){
	if ($_POST["submit"] == "Add") {
		$trackingNum = trim($_POST["tracking"], " ");
		$carrier = $_POST["carrier"];
		$comment = $_POST["comment"];
		
		//echo $_POST['action'];

		
		//----begin trackhive code to add a parcel to your account
		try {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, "https://api.trackinghive.com/trackings");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);

			curl_setopt($ch, CURLOPT_POST, TRUE);

			curl_setopt($ch, CURLOPT_POSTFIELDS, "{
			  \"tracking_number\": \"" . $trackingNum . "\",
			  \"slug\": \"" . $carrier . "\",
			  \"source\": \"ParcelPony\",
			  \"customer_name\": \"" . $comment . "\",
			  \"custom_fields\": {
			    \"direction\":\"". $pageDirection ."\"
			  }
			}");

			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			  "Content-Type: application/json",
			  "Authorization: Bearer " . $bearerToken
			));

			$response = curl_exec($ch);

			// Check the return value of curl_exec(), too
			if ($response === false) {
				throw new Exception(curl_error($ch), curl_errno($ch));
			}

			curl_close($ch);
		} catch (Exception $e) {
			trigger_error(sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
		}

		//var_dump($response);

		//----end trackhive code to add a parcel to your account

		$json = json_decode($response, false);
		
		//print_r($json);
		
		if ($json->meta->code == 200) {
			$_id = $json->data->_id;
			
			//echo '<font color="green">Package added!</font><br /><br />';
			$alertMessage = "Added";
			//echo $alertMessage;
			//addSubscription($_id, $bearerToken);
		} else if ($json->meta->code == 400) {
			echo '<font color="red">Could not add package.</font><br /><br />';
		}
		
		
	}
}
	
?>

<div class="main">

	<?php 
	//echo $alertMessage;
	
	if ($alertMessage == "Added") { ?>
	<div class="alert" id="alert-added"> 
		<font color="green">Package added!</font>
		<script type="text/javascript">
			$('#alert-added').show(function(){
				$(this).fadeOut(3000);
			});
		</script>
	</div>
	<?php } else if ($alertMessage == "Removed") { ?>
	<div class="alert" id="alert-removed"> 
		<font color="red">Package removed.</font>	 
		<script type="text/javascript">
			$('#alert-removed').show(function(){
				$(this).fadeOut(3000);
			});
		</script> 
	</div>
	<?php } ?>
	
	<div class="package-inbox-view">
		<div class="container">
			<div class="content">
				<div class="package-view">
					<!-- Logo -->
					<div class="logo-view">
						<a href="index.php">
							<img class="logo" src="ParcelPonyLogo.png" />
						</a>
					</div>
						
					<div class="package-container">
						<div class="nav">
							<a href="index.php">Incoming</a> | <a href="outgoing.php">Outgoing</a>
						</div>
						<!-- Enter tracking number -->
						<div class="tracking">
							<form method="post" class="track-form">
							<input type="text" name="tracking" placeholder="tracking number" class="track-form-input">

							<input type="text" name="comment" placeholder="title (optional)" class="track-form-input">
							
							<select name="carrier" id="carrier" class="track-form-input">
							
							<?php
							/*Get all carriers*/
							try {
								$ch = curl_init();

								curl_setopt($ch, CURLOPT_URL, "https://api.trackinghive.com/couriers/list");
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
								curl_setopt($ch, CURLOPT_HEADER, FALSE);

								$response = curl_exec($ch);
								curl_close($ch);
								
								$carrier_json = json_decode($response, false);

								usort($carrier_json->data, function ($a, $b) {
									return $a->title <=> $b->title;
								});
								
								foreach ($carrier_json->data as $carriers) {
										echo '<option value="' . $carriers->slug . '">' . $carriers->title . '</option>';
								}
							}catch (Exception $e) {
								echo "<h1>Error getting parcels.</h1> <br />There might be a problem connecting to TrackingHive or you haven't specified your API Token.";
							}
							?>
								
							</select>

							<input type="submit" name="submit" value="Add" class="track-form-input">
						</div>
