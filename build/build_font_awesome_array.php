<?php

require_once( dirname( __FILE__ ) . '/../../../../wp-load.php' ); // get

//echo '###';exit;

//function fetch_and_parse_table() {
//	// URL of the webpage that contains the table
//	$url = 'https://www.fontawesomecheatsheet.com/font-awesome-cheatsheet-5x/';
//
//	// Fetch the webpage content using WordPress HTTP API
//	$response = wp_remote_get( $url );
//	if ( is_wp_error( $response ) ) {
//		return 'Failed to fetch the webpage!';
//	}
//	$html_content = wp_remote_retrieve_body( $response );
//
//
//	// Initialize DOMDocument to parse the HTML content
//	$dom = new DOMDocument;
//	libxml_use_internal_errors( true ); // Suppress HTML parsing errors
//	$dom->loadHTML( $html_content );
//	libxml_clear_errors();
//
//	// Find the table with the ID "icons"
//	$table = $dom->getElementById( 'icons' );
//	if ( ! $table ) {
//		return 'Table with ID "icons" not found!';
//	}
//
//	// Iterate through the table rows
//	$result = [];
//	$rows   = $table->getElementsByTagName( 'tbody' )->item( 0 )->getElementsByTagName( 'tr' );
//	foreach ( $rows as $row ) {
//		// Extract class name from the "Code" column
//		$code_column = $row->getElementsByTagName( 'td' )->item( 3 );
//		$class_name_raw = $code_column->getElementsByTagName('span')->item(2)->nodeValue;
//		$class_name = trim(str_replace(['"', '\\'], '', $class_name_raw));
//
//
//		// Extract search terms from the "Search" column
//		$search_column = $row->getElementsByTagName( 'td' )->item( 5 );
//		$search_terms  = [];
//		foreach ( $search_column->getElementsByTagName( 'span' ) as $term ) {
//			$search_terms[] = trim( $term->nodeValue );
//		}
//
//		// Add the extracted information to the result
//		$result[] = [
//			'title'       => $class_name,
//			'searchTerms' => $search_terms,
//		];
//	}
//
//	return json_encode( $result, JSON_PRETTY_PRINT );
//}
//
//// Example usage
//echo fetch_and_parse_table();
//


// Fetch the JavaScript file
//$response = wp_remote_get('https://raw.githubusercontent.com/FortAwesome/Font-Awesome/5.x/js/all.js');
////$response = wp_remote_get('https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/js/all.js');
//
//if (is_wp_error($response)) {
//    // Handle error
//    echo 'Error: ' . $response->get_error_message();
//} else {
//    // Get the body of the response
//    $js = wp_remote_retrieve_body($response);
//
//    // Use a regular expression to extract the icon names and their corresponding class prefixes
//    preg_match_all('/var icons = \{(.*?)\};\s*bunker\(function \(\) \{\s*defineIcons\(\'(fab|fas|far|fal|fad)\', icons\);\s*\}\);/s', $js, $matches, PREG_SET_ORDER);
//
//    // Initialize an array to hold the formatted icons
//    $icons = [];
//
//    // Iterate over the matches
//    foreach ($matches as $match) {
//        // Get the class prefix
//        $classPrefix = $match[2];
//
//        // Get the icon names
//        $iconNames = explode(',', str_replace(['"', ':[]'], '', $match[1]));
//
//        // Iterate over the icon names
//        foreach ($iconNames as $iconName) {
//            // Add the icon to the array in the specified format
//            $icons[] = [
//                'title' => $classPrefix . ' fa-' . $iconName,
//                'searchTerms' => [],
//            ];
//        }
//    }
//
//    // Print the icons as JSON
//    echo json_encode($icons, JSON_PRETTY_PRINT);
//}



//exit;

/*  old way
$result   = wp_remote_get( "https://fontawesome.com/cheatsheet" );
$out      = $result['body'];
$start    = "window.__inline_data__ = "; // start here
$end      = "</script>";
$startsAt = strpos( $out, $start ) + strlen( $start );
$endsAt   = strpos( $out, $end, $startsAt );
$result   = substr( $out, $startsAt, $endsAt - $startsAt );
$result   = json_decode( $result );
$icons    = array();
foreach ( $result[2]->data as $data ) {

	if ( $data->type == 'icon' ) {
		// skip pro only icons
		if ( empty( $data->attributes->membership->free ) ) {
			continue;
		}

		$types = $data->attributes->membership->free;
		foreach ( $types as $type ) {
			$prefix = '';
			$weight = 0;
			if ( $type == 'brands' ) {
				$prefix = 'fab ';
				$weight = 400;
			} elseif ( $type == 'solid' ) {
				$prefix = 'fas ';
				$weight = 900;
			} elseif ( $type == 'regular' ) {
				$prefix = 'far ';
				$weight = 400;
			} else{
				// must be pro
				continue;
			}
			$icons[ $prefix . "fa-" . $data->id ] = $data->attributes->unicode ;
			$map[ $prefix . "fa-" . $data->id ] = array('unicode'=>$data->attributes->unicode,'file'=>"fa-{$type}-{$weight}.ttf") ;
		}

	}

}
//print_r($icons);exit;
//echo json_encode($icons);

// output const MAP
echo "const MAP = [";
foreach($map as $class => $info){
	$unicode = $info['unicode'];
	$file = $info['file'];
	echo "'".$class."' => ['unicode' => '\u$unicode', 'file' => '$file' ], \n";
}
echo "];";
exit;


// output it for a nice copy/pase
echo "array( \n";
foreach($icons as $class=>$unicode){
	echo "'".$class."' => '".$unicode."', \n";
}
echo ");";

exit;


*/

/**
 * Build the JSON needed for the fa-iconpicker JS
 *
 * @param $v
 * @param $pro
 *
 * @return false|string
 */
function get_font_awesome_data($v = 6, $pro = false ) {
	require_once 'spyc.php';// include the yml parser

	if ( $pro ) {
		// we need to download the pro yml files from fontawesome site.
		if ( $v == 5 ) {
			$body = file_get_contents( __DIR__ . '/v5.yml' );
		}else{
			$body = file_get_contents( __DIR__ . '/v6.yml' );
		}
	}else{
		// Base URL for Font Awesome metadata on GitHub
		if ( $v == 5 ) {
			$base_url = 'https://github.com/FortAwesome/Font-Awesome/raw/5.x/metadata/icons.yml';
		}else{
			$base_url = 'https://github.com/FortAwesome/Font-Awesome/raw/6.x/metadata/icons.yml';
		}
		// Use WordPress function to get the YAML content
		$response = wp_remote_get($base_url);
		$body = wp_remote_retrieve_body($response);
	}


	// Parse the YAML content with Spyc
	$icons = Spyc::YAMLLoadString($body);

	$formatted_data = [];

	$style_arr = array(
		'solid' => $v==6 ? 'fa-solid' : 'fas',
		'regular' => $v==6 ? 'fa-regular' : 'far',
		'brands' => $v==6 ? 'fa-brands' : 'fab',
		'light' => $v==6 ? 'fa-light' : 'fal',
		'duotone' => $v==6 ? 'fa-duotone' : 'fad',
		'thin' => $v==6 ? 'fa-thin' : '',
	);

	foreach ($icons as $icon_name => $icon_data) {

		if ( ! empty( $icon_data['styles'] ) ) {
			foreach ( $icon_data['styles'] as $style ) {

				$class_name = $style_arr[$style] . " fa-" . $icon_name;
				$search_terms = $icon_data['search']['terms'] ?? [];

				$formatted_data[] = [
					"title" => $class_name,
					"searchTerms" => $search_terms
				];

			}
		}


	}

//	print_r( $formatted_data );exit;
	return json_encode( $formatted_data );
}

//echo get_font_awesome_data(5 );
echo get_font_awesome_data(6 );
//echo get_font_awesome_data(5, true );
//echo get_font_awesome_data(6, true );
exit;