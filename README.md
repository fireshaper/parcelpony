**ParcelPony - Selfhosted Parcel Tracking**

**Because Trackhive is no longer active I have made the decision to abandon this project. If another API like trackhive becomes available I may pick this back up in the future. Do not expect any updates to this project.**

Requirements:
 - A web server
 - PHP
 - An API key from https://my.trackinghive.com/
 
Features:
 - Track parcels from USPS, UPS, FedEx, DHL, and more
 - Responsive design that works on desktop and mobile

Instructions:
 1. Create an account on TrackingHive and log into the Dashboard
 2. Click API in the bar on the left
 3. Click the Plus button to create an API key
 	NOTE: The site was slow for me to make my first API key and I ended up making way too many at once. If you don't see the key right away, try going back to the overview and see if the page loads. You might have to wait a few minutes for the site to catch up with you.
 4. Download the ParcelPony release and unzip the files to a folder on your web server
 5. Copy config.php.dist to config.php and paste your API key where it asks for the BearerToken
 6. Go to http://localhost/parcelpony and start adding parcels to track
 
 
 NOTE: You can enable email notifications on TrackingHive for all parcels you add, the name of the package in ParcelPony is set to the First Name field on TrackingHive.
