# PILOT
PILOT - Planetary Image Locator Tool

Source code for PILOT website (https://pilot.wr.usgs.gov). PILOT is an online data portal used by planetary scientists and
cartographers to search through the raw archives of the PDS Cartography and Imaging Science Node. PILOT relies on an image
metadata database with a schema similar to the Unified Planetary Coordinate (UPC) database maintained by the USGS Astrogeology
Science Center. The development of PILOT is supported by the USGS Astrogeology Science Center and the NASA/USGS PDS
Cartography and Imaging Science Node.

# Dependencies

* Apache/PHP
* PostgreSQL with PostGIS spatial extension
* USGS-Astrogeology distribution of AstroWebMaps
* A footprint database based on the UPC schema

# Install

* Download the repository
* At the top level of pilot, download https://github.com/USGS-Astrogeology/AstroWebMaps
* Copy the file configure-EDIT.php to configure.php. Edit the config fields.
  * Set database parameters. PILOT requires a database based on the UPC schema.
  * PILOT can search up to three databases. The current UPC search database is split into three different parts (Mars, Moon, the rest of the solar system).
  * If you are using a single database in the UPC schema, use the same parameters for each database.
  * Astrogeology URL's should work unless you are setting up the site on internal network.
  * Jquery libs are included in the download.
  * Map libs should point to the downloaded AstroWebMaps. The path may need to be tweaked.
* One javascript file must be tweaked to set image path: js/pilotPanels.js. Set imagePath on line 372. It should point to the image directory inside the downloaded AstroWebMaps.
* On the top level of pilot, add a web-writable "log" to log the queries.
* On the top level of pilot, add a web-writable "tmp" directory. This is for storing downloadable CSV or script files.

Note: a new schema for the UPC is in development.  PILOT is going through a major version change to reflect these changes.
  
  
