This builds the icons JSON for the fa-iconpicker JS, follow these steps to build them.

1. Download the latest zip packages from font awesome (we have a pro membership), look in metadata/icons.yml, ovewrite the v6/v6 files in this folder.
2. open the build_font_awesome_array.php in a browser, change the commented out function at the bottom of the file to get the needed JSON.
3. Copy the JSON output to the JS file (around line 1000)