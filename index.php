<?php

$pageTitle = "ParcelPony";
$pageDirection = "inbound";
include("top.php");

$config = include("config.php");

//----- Global Stuff -----//

/*Get an API Token from my.trackinghive.com and put it here */
$bearerToken = $config['BearerToken'];

?>
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
								//echo  "Is goodbye empty? " . empty($json->{'data'});
								
								try {
								foreach ($json->data as $mydata) {
									$custom_fields = getCustomFields(isset($mydata->custom_fields) ? $mydata->custom_fields : null); //explode(':', $mydata->custom_fields);
									$direction = "inbound";
									$infoStatus = $mydata->current_status;
									$parcelID = $mydata->_id;
									$carrier_slug = $mydata->slug;
									$scheduled = false;
									$comment = '';
									if (isset($custom_fields->direction)) {
										$direction = $custom_fields->direction;
									}
									if (isset($mydata->customer_name)){
										$comment = $mydata->customer_name;
									} else if (isset($custom_fields->comment)){
										$comment = $custom_fields->comment;
									}
									
									//echo $json->meta->code . "<br />";
									if ($direction == "inbound") {
										if ($mydata->trackings->expected_delivery != null){
											$expTS = $mydata->trackings->expected_delivery;
											$expDelivery = date("m/d/Y", strtotime($expTS));
											
											if ($expDelivery == $today){
												$expectedDelivery = "Expected Delivery Today";
											}
											else {
												$expectedDelivery = "Expected Delivery is " . date("l, m/d/y", strtotime($expTS));
											}
											
											$scheduled = true;
										} else {
											if ($mydata->trackings->tag == "Delivered") {
												$shipTS = $mydata->trackings->shipment_delivery_date;
												$shipmentDelivery = date("m/d/Y", strtotime($shipTS));
																							
												$shipSecsAgo = $todayTS - strtotime($shipTS);
												$shipDaysAgo = $shipSecsAgo / 86400;
												
												if ($shipmentDelivery == $today) {
													$expectedDelivery = "Today";
												} else {											
													$expectedDelivery = "About " . ceil($shipDaysAgo) . " Days Ago";
												}
											} else {
												//echo "no update in " . $mydata->trackings->delivery_time . " days";
												$expectedDelivery = "Last updated about " . $mydata->trackings->delivery_time . " days ago";  
											}
										}
										
										if ($scheduled == false){
											//echo "checkpoints = ". count($mydata->trackings->checkpoints);
											if (count($mydata->trackings->checkpoints) > 0) {
												if (end($mydata->trackings->checkpoints)->checkpoint_time) {
													$timestamp = end($mydata->trackings->checkpoints)->checkpoint_time;
													$tsMonth = date("F", strtotime($timestamp));
													$tsDay = date("j", strtotime($timestamp));
													$tsTime = date("H:i:s", strtotime($timestamp));
												} 
												else {
													$tsMonth = "NULL";
													$tsDay = "NULL";
													$tsTime = "NULL";
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
										
										foreach ($carrier_json->data as $carriers){
											if ($carrier_slug == $carriers->slug){
												$carrier = $carriers->title;
											}
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
														$carrierNull = false;
														
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
															default:
																$carrier_link = '';
																$carrierNull = true;
																break;
														}
														
													if ($carrierNull == false) {
													?>
													<a href="<?php echo $carrier_link . $trackingNum; ?>" target="_new">
														<span class="info">
															<div class="fa fa-info-circle"></div>
															<span class="track">track</span>
														</span>
													</a>
													<?php 
													} else {
													?>
														
														<span class="info">
															N/A
														</span>
													
													<?php
													}
													?>
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
																} else if ($infoStatus == "OutForDelivery") {
																	$statusStyle = 'style="background-color: #f7d418 !important;border-color: #f7d418 !important;"';
																	$trackStatus = "Out for Delivery";
																} else if ($infoStatus == "Pending") {
																	$statusStyle = 'style="background-color: #858585 !important;border-color: #858585 !important;"';
																	$trackStatus = "Pending";
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
																} else if ($infoStatus == "OutForDelivery") {
																	$statusStyle = 'style="border-color: #f7d418 !important;"';
																} else if ($infoStatus == "Pending") {
																	$statusStyle = 'style="border-color: #858585 !important;"';
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
											<div class="media-click-text">
												Click to Expand
											</div>
											<div class="media-track">
											<ul class="fa-ul">
											<?php
																																	
												$count = 0;
												foreach (array_reverse($mydata->trackings->checkpoints) as $checkpoint) {
													$cTS = $checkpoint->checkpoint_time;
													$checkpointTime = date("m-d-Y H:i:s", strtotime($cTS));
													
													if ($count == 0 && count($mydata->trackings->checkpoints) == 1) {
														echo '<li><span class="fa-il"><i class="fa fa-circle"></i></span> ' . $checkpointTime . "   " . $checkpoint->location . " " . $checkpoint->message . "</li>";
													} else if ($count == 0){
														echo '<li><span class="fa-il"><i class="fa fa-circle"></i></span><b> ' . $checkpointTime . "   " . $checkpoint->location . " " . $checkpoint->message . "</b></li>";
														echo '<li>&nbsp;<span class="fa-il"><i class="fas fa-long-arrow-alt-up"></i></span>';
													} else if ($count == count($mydata->trackings->checkpoints)-1){
														echo '<li><span class="fa-il"><i class="far fa-circle"></i></span> ' . $checkpointTime . "   " . $checkpoint->location . " " . $checkpoint->message . "</li>";
													} else {
														echo '<li><span class="fa-il"><i class="far fa-circle"></i></span> ' . $checkpointTime . "   " . $checkpoint->location . " " . $checkpoint->message . "</li>";
														echo '<li>&nbsp;<span class="fa-il"><i class="fas fa-long-arrow-alt-up"></i></span>';
													}
													//echo $count . "<br />";
													$count++;
												}
											?>
											</ul>
										</div>
										</div>
									</div>
								</div>
							<?php 
									}
								}
								}catch (Exception $e) {
									if (empty($json->{'data'}) == 1){
										echo "<h1>Error getting parcels.</h1> <br />There was a problem connecting to TrackingHive. Please try again in a few minutes.";
									} else {
										echo "<h1>Error getting parcels.</h1> <br />There might be a problem connecting to TrackingHive or you haven't specified your API Token. <br /><br />Exception: " . $e;
									}
								}
							?>
							
						</div>

<?php include("bottom.php"); ?>
