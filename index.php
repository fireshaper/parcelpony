<html>
<head>
<link href="styles.css" rel="stylesheet" type="text/css"/>
<link href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" rel="stylesheet" type="text/css" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
</head>

<body>
<div class="main">
	<div class="package-inbox-view">
		<div class="container">
			<div class="content">
				<div class="package-view">
					<div class="package-container">
						<?php

							/*Get an API Token from my.trackinghive.com and put it here */
							$bearerToken = '';

							$comment = "No title";
							
							$now = new DateTime();
							$today = $now->format('m/d/Y');
							
							if(isset($_POST['action']) && isset($_POST['id'])) {
								if ($_POST['action'] == 'Delete') {
									deleteParcel($_POST['id'], $bearerToken);
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
									echo '<font color="red">Package removed!</font><br /><br />';
								}
							}

							// Check if a parcel was added on page load//
							if (isset($_POST["tracking"])){
								$trackingNum = trim($_POST["tracking"], " ");
								$carrier = $_POST["carrier"];
								$comment = $_POST["comment"];

								/*
								if (!is_file($file)) {
									file_put_contents($file, $trackingNum);
								} else {
									$parcelFile = fopen($file, 'a');
									fwrite($parcelFile, $trackingNum . "," . $carrier . PHP_EOL);
									fclose($parcelFile);
								}
								*/
								
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
									  \"custom_fields\": \"comment:" . $comment . "\"
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

								$json = json_decode($response, true);
								
								//print_r($json);
								
								if ($json['meta']['code'] == 200) {
									echo '<font color="green">Package added!</font><br /><br />';
								}
							}
						?>
						
						<!-- Enter tracking number -->
						<div class="tracking">
							<form action="index.php" method="post" class="track-form">
							<input type="text" name="tracking" placeholder="tracking number" class="track-form-input">

							<input type="text" name="comment" placeholder="title (optional)" class="track-form-input">

							<select name="carrier" id="carrier" class="track-form-input">
							  <option value="usps">USPS</option>
							  <option value="ups">UPS</option>
							  <option value="fedex">FedEx</option>
							  <option value="dhl">DHL</option>
							</select>

							<input type="submit" value="Add" class="track-form-input">
						</div>

						<!-- Show parcels -->
						<div class="parcels">
							<?php
								//----begin trackhive code to get list of parcels
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

								//var_dump($response);

								//----end trackhive code to get list of parcels

								$json = json_decode($response, false);

								//print_r($json);
								
								try {
								foreach ($json->data as $mydata) {
									$custom_fields = explode(':', $mydata->custom_fields);
									$comment = $custom_fields[1];
									$infoStatus = $mydata->current_status;
									$parcelID = $mydata->_id;
									$carrier_slug = $mydata->slug;
									$scheduled = false;
									
									if ($mydata->trackings->expected_delivery != null){
										$expTS = $mydata->trackings->expected_delivery;
										$expectedDelivery = "Expected Delivery is " . date("m/d/y", strtotime($expTS));
										$scheduled = true;
									} else {
										if ($mydata->trackings->tag == "Delivered") {
											$shipTS = $mydata->trackings->shipment_delivery_date;
											$shipmentDelivery = date("m/d/y", strtotime($shipTS));
											
											if ($shipmentDelivery == $today) {
												$expectedDelivery = "Today";
											} else {											
												$expectedDelivery = "About " . $mydata->trackings->delivery_time . " Days Ago";
											}
										} else {
											$expectedDelivery = $mydata->trackings->delivery_time . " Days Ago";  
										}
									}
									
									if ($scheduled == false){
										if (count($mydata->trackings->checkpoints) > 0) {
											if (end($mydata->trackings->checkpoints)->checkpoint_time) {
												$timestamp = end($mydata->trackings->checkpoints)->checkpoint_time;
												$tsMonth = date("F", strtotime($timestamp));
												$tsDay = date("j", strtotime($timestamp));
												$tsTime = date("H:i:s", strtotime($timestamp));
											} 
										} else {
											$timestamp = $mydata->created;
											$tsMonth = date("F", strtotime($timestamp));
											$tsDay = date("j", strtotime($timestamp));
											$tsTime = date("H:i:s", strtotime($timestamp));
										}
									} else {
										$tsMonth = date("F", strtotime($expTS));
										$tsDay = date("j", strtotime($expTS));
										$tsTime = "Scheduled";
									}
									
									if (count($mydata->trackings->checkpoints) > 0) {
										if (end($mydata->trackings->checkpoints)->location) {
											$lastLoc = end($mydata->trackings->checkpoints)->location;
										} else {
										$lastLoc = "N/A";
									}
									} else {
										$lastLoc = "N/A";
									}
									
									if (count($mydata->trackings->checkpoints) > 0) {
										if (end($mydata->trackings->checkpoints)->message) {
											$infoMore = end($mydata->trackings->checkpoints)->message;
										} else {
											$infoMore = "N/A";
										}
									} else {
										$infoMore = "N/A";
									}
									
									switch ($carrier_slug){
										case 'usps':
											$carrier = 'USPS';
											break;
										case 'ups':
											$carrier = 'UPS';
											break;
										case 'fedex':
											$carrier = 'FedEx';
											break;
										case 'dhl':
											$carrier = 'DHL';
											break;
									}

									$trackingNum = $mydata->tracking_number;
									
									$modTS = $mydata->modified;
									$modTime = strtotime($modTS);
									$nowTS = $now->getTimestamp();
									$modifiedTime = "About " . date('g', $nowTS - $modTime) . " hours ago";
									//echo "About " . date('g', $nowTS - $modTime) . " hours ago";
										
								?>
								<div class="parcel-item">	
									<div class="row">
										<div class="media-box">
											<div class="media">
												<div class="media-left">
													<div class="datetime-info">
														<div class="datetime-info-month"> <?php echo $tsMonth;  ?> </div>
														<div class="datetime-info-day"> <?php echo $tsDay;  ?> </div>
														<div class="datetime-info-time"> <?php echo $tsTime;  ?> </div>
													</div>
													<?php
														switch ($carrier_slug){
															case 'usps':
																$carrier_link = 'https://tools.usps.com/go/TrackConfirmAction?qtc_tLabels1=';
																break;
															case 'ups':
																$carrier_link = 'https://www.ups.com/track?loc=en_US&tracknum=';
																break;
															case 'fedex':
																$carrier_link = 'https://www.fedex.com/apps/fedextrack/?tracknumbers=';
																break;
															case 'dhl':
																$carrier_link = 'https://www.dhl.com/en/express/tracking.html?AWB=';
																break;
														}
													?>
													<a href="<?php echo $carrier_link . $trackingNum; ?>" target="_new">
														<span class="info">
															<div class="fa fa-info-circle"></div>
															<span class="track">track</span>
														</span>
													</a>
												</div>
												<div class="media-body">
													<div class="info-container">
														<div class="info-top-line">
															<?php 
																
																if ($infoStatus == "InTransit") {
																	$statusStyle = 'style="background-color: #0d97f1 !important;border-color: #0d97f1 !important;"';
																	$trackStatus = "In Transit";
																} else if ($infoStatus == "InfoReceived") {
																	$statusStyle = 'style="background-color: #373852 !important;border-color: #373852 !important;"';
																	$trackStatus = "Information Received";
																} else if ($infoStatus == "Delivered") {
																	$statusStyle = 'style="background-color: #3fb00b !important;border-color: #3fb00b !important;"';
																	$trackStatus = "Delivered";
																} else {
																	$statusStyle = 'style="background-color: #000 !important;border-color: #000 !important;"';
																	$trackStatus = "N/A";
																}
															?>
															<div class="info-status" <?php echo $statusStyle; ?>> <?php echo $trackStatus;  ?> </div>
															
															<?php 
																
																if ($infoStatus == "InTransit") {
																	$statusStyle = 'style="border-color: #0d97f1 !important;"';
																} else if ($infoStatus == "InfoReceived") {
																	$statusStyle = 'style="border-color: #373852 !important;"';
																} else if ($infoStatus == "Delivered") {
																	$statusStyle = 'style="border-color: #3fb00b !important;"';
																} else {
																	$statusStyle = 'style="border-color: #000 !important;"';
																}
															?>
															<div class="info-days" <?php echo $statusStyle; ?>> <?php echo $expectedDelivery  ?> </div>
														</div>
														<div class="info-middle">
															<div class="info-more"> <?php echo $infoMore;  ?> </div>
															<span class="info-location"> 
																<i class="fa fa-map-marker loc">
																	<?php echo $lastLoc;  ?> 
																</i>
															</span>
														</div>
														<div class="info-tracking">
															<div class="info-tracking-number"> 
																<div class="carrier"> <?php echo $carrier; ?> </div>
																<div class="trackingNum"> <?php echo $trackingNum; ?> </div>
															</div>
															<div class="info-title"> <?php echo $comment; ?> </div>
														</div>
													</div>
												</div>
											</div>
											<div class="media-right"> 
												<div class="last-update"> <?php echo $modifiedTime; ?> </div>
												<div class="delete-button"> 
													<!--<i class="fa fa-trash"></i>
													<span>Delete Parcel</span> -->
													<form method="post" action="index.php">
														<input type="submit" name="action" value="Delete"/>
														<input type="hidden" name="id" value="<?php echo $parcelID; ?>"/>
													</form>
													</form>
												</div>
											</div>
										</div>
									</div>
								</div>
							<?php }
							} catch (Exception $e) {
								echo "Trouble connecting to Trackhive, please try again."; 
							} ?>	
							
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>

iv>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>

e try again."; 
							} ?>	
							
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>

