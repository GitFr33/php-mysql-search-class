<?php

function show_search_form(){
	if($_GET['keywords']){
		$value = "value=\"" . htmlentities($_GET['keywords']) ."\"";
	}else{
		$value = '';
	}
	$search_form = '
	<form method="get" id="header_search_form" action="search">
		<input type="search" class="menu_link" name="keywords" placeholder="Search" ' . $value . ' required>
		<input type="submit" value="GO" class="menu_link">
	</form>';
	
	return $search_form;
	}

function show_search(){
	
	if($_GET['keywords']){
		global $DB;
	
		# configure search engine
		
		# Set which table to look in 
		$table = "pages";
		
		# Set which of the feilds in that table should be searched
		$look_in = array('id','title','description','category','url','content','keywords');
		
		# Set which fields results should be ranked by (a filed that is encluded twice will be given twice the ranking weight)
		$rank_by = array('title', 'title', 'description','url');
		
		# Create the search object.
			$search = new search($_GET['keywords'],$table, $look_in, $rank_by);
		
		# Optionally set search to match any keyword instead of all keywords
			$search->settings['greedy'] = true;
		
		# Optionally set reqired conditions. 
			# For example you could have a categories drop down in on your search page and pass the results to this method. 
			# Or set the field to 'status' and the value to 'active' to only show items that are set to active in the database.
			# $search->set_required_conditions($field, $value);
			
		# Optionally set a custom sort 
			# Can be used to sort results by date or alphebetically rather then by relevence. (Second argument is optional and defults to SORT_ASC.)
			# $search->set_sortby('date', SORT_DESC);
		
		# Return results formated as html edit the show_search_result_block() method to customize what fields are shown and how.
			# This urrently uses the photosynthesis paginate class to handle mutiple pages of results. That could be removed or swapped for your favorite pagenator. 
			$search_results_html = $search->get_results_html();
		
		# Alternatly return an array of ids instead
			# Set the field to use.
			$search->settings['id_field'] = 'id';
		
			# Get a sorted single level array matching ids.
			$ids = $search->get_results_ids();
		
		# The get_results_message() method returns a formatted message about how many results were found
		$out .= "<span class=\"small\">Found " . $search->get_results_message() ."</span> <br /><br />";
		$out .= $search_results;

	}else{
		$out = "Please type what you would like to find in the search box at the upper right of the page."; 
	}
 
	return $out;
}

?>
