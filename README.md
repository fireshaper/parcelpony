**ParcelPony - Selfhosted Parcel Tracking**

Requirements:
 - A web server
 - PHP
 - An API key from https://my.trackinghive.com/
 
Features:
 - Track parcels from USPS, UPS, FedEx, and DHL
 - Responsive design that works on desktop and mobile

Instructions:
 1. Create an account on TrackingHive and log into the Dashboard
 2. Click API in the bar on the left
 3. Click the Plus button to create an API key
 	NOTE: The site was slow for me to make my first API key and I ended up making way too many at once. If you don't see the key right away, try going back to the overview and see if the page loads. You might have to wait a few minutes for the site to catch up with you.
 4. Download the ParcelPony release and unzip the files to a folder on your web server
 4. Copy your API key and paste it into index.php where it asks for the BearerToken
 5. (Optional) There's also a page called getjson.php that just pulls the json for a package (for testing). If you want to use this page you will need to paste the API key on that page too.
 6. Go to http://localhost/parcelpony and start adding parcels to track
 
 
