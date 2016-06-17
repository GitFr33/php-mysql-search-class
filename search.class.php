<?php
/*
# Things That need to be customized
    $this->look_in
    $this->table
    $score_var (the db fields for ranking results 
*/
class search{
    var $keywords_array;
    var $keywords;
    var $keyword;
    var $results;
    var $results_message;
    var $keyword_list;
    var $required_conditions;
    var $results_count;
    var $look_in;
    var $return_columns;
    var $sortby;
    var $sort_order;
    var $rank_by;
    var $settings;
/* READ ME 

What the Heck is going on??
search_split_terms() 
    Replace * with %
    If $keyword is quoted send to search_transform_term() which replaces commas and whitespac with {PLACEHOLDERS}
    Split $this->keywords by spaces and commas and Populate $this->keywords_array with parts
search_db_escape_terms();
    creates $keywords_db var replacing spaces with [[:>:]] uses Addsolashes as well as an inscrutable regex escaper 
search_rx_escape_terms(){
    takes the output from search_db_escape_terms() and does this to it. ?
    $out = array();
    foreach($this->keywords_array as $keyword){
        $out[] = '\b'.preg_quote($keyword, '/').'\b';
    }
 
*/
            
function __construct($keywords, $table, $look_in, $rank_by){
    $this->keywords = trim($keywords);
    $this->keywords_array = $keywords;
    $this->table = $table;      // The SQL table to search in
    $this->look_in = $look_in;  //an array of database field names
    $this->rank_by = $rank_by;
    $this->settings['greedy'] = false; // Set to true to match any keywords
    $this->settings['id_field'] = 'id'; // Set to the uneque id field of table 
   }

function search_split_terms(){
    # Replace * with %
    $this->keywords = str_replace('*', '%' , $this->keywords);
    
    
    # Send anything between quots to search_transform_term() which replaces commas and whitespace with {PLACEHOLDERS}
    $this->keywords = preg_replace_callback("~\"(.*?)\"~", "search::search_transform_term", $this->keywords);
    
    
    # Split $this->keywords by spaces and commas and Populate $this->keywords_array with parts
    $this->keywords_array = preg_split("/\s+|,/", $this->keywords);
    
    
    # convert the {COMMA} and {WHITESPACE} back within each row of $this->keywords_array
    foreach($this->keywords_array as $key => $keyword){
        $keyword = preg_replace_callback("~\{WHITESPACE-([0-9]+)\}~", function ($stuff) { return chr($stuff[1]);}, $keyword);
        
        $keyword = preg_replace("/\{COMMA\}/", ",", $keyword);

        $this->keywords_array[$key] = $keyword;
    }
}

function search_transform_term($keyword){

    $keyword[1] = preg_replace_callback("~(\s)~", function($match) { return '{WHITESPACE-'.ord($match[1]).'}';}, $keyword[1]);
    
    $keyword = preg_replace("/,/", "{COMMA}", $keyword[1]);
    return $keyword;
}

function search_escape_rlike($keyword){
    return preg_replace("~([.\[\]*^\$])~", '\\\$1', $keyword);
}

function search_db_escape_terms(){
    $out = array();
    foreach($this->keywords_array as $keyword){
        $out[] = str_replace('%[[:>:]]','',str_replace('[[:<:]]%','','[[:<:]]'.AddSlashes($this->search_escape_rlike($keyword)).'[[:>:]]'));
    }
    return $out;
}

function set_required_conditions($field, $value){
            
    $this->required_conditions[$field] = $value;
    testit('$rc in set rc',$this->required_conditions);
}




function search_perform(){

    $this->search_split_terms();
    $keywords_db = $this->search_db_escape_terms();
    $keywords_rx = $this->search_rx_escape_terms($keywords_db);

    $parts = array();
    
    # Greedy search (match any keywords)
    if($this->settings['greedy'] == true){
        //$intermed = '(';
        foreach($keywords_db as $keyword_db){
        
            foreach($this->look_in as $look_in){
                $parts[]="$look_in RLIKE '$keyword_db'";
                
            }
        }
        $parts = implode(' OR ', $parts);
    }else{
    # Un greedy (match all keywords)
        $intermed = '(';
        foreach($keywords_db as $keyword_db){
            
            foreach($this->look_in as $look_in){
                $parts[]="$intermed $look_in RLIKE '$keyword_db'";
                $intermed = ' OR';
            }
            $intermed = ') AND (';
            
        }
        $parts = implode('', $parts).")";
    }
    if($this->required_conditions){
        
        $rc = $this->required_conditions;
        testit('$rc at beginning of if($rc)',$rc);
        if(!$this->keywords){
### if there is no keywords but there is a required condition then delete the $parts and query just based on required conditions
            unset($parts);
            unset($and_parts);

            foreach($rc as $field => $value){
                
# If there are multipuls VALUES of a required_conditions loop it with an sql OR
                if(is_array($value)){
                    //testit('the RC value is an array', $value);
                    foreach($value as $key => $value){
                        $value = '[[:<:]]'.AddSlashes($this->search_escape_rlike($value)).'[[:>:]]';
                        $parts =" $parts $or $field RLIKE '$value'";
                        $or = "OR";
                    }
                }else{
                    $value = '[[:<:]]'.AddSlashes($this->search_escape_rlike($value)).'[[:>:]]';
                    $parts = "$field RLIKE '$value' $and_parts";
                    $and_parts = "AND ($parts)";
                }
            }           
        }else{
        # If there are keyword(s) AND required condition(s)

            foreach($rc as $field => $value){
                # If there are multipuls VALUES of a required_conditions loop it with an sql OR
                if(is_array($value)){
                    testit('yup the RC value is an array', $value);
                    foreach($value as $key => $value){
                        //$value = '[[:<:]]'.AddSlashes($this->search_escape_rlike($value)).'[[:>:]]';
                        $rc_or = "$field RLIKE '$value' AND ($parts) $or $rc_or";
                        $or = "OR";
                        
                        }
                        $parts = $rc_or;
                        
                }else{
                
                    if($value != ""){
                        //testit(' $rc[$value] in if($this->keywords){}',$value);
                        $value = '[[:<:]]'.AddSlashes($this->search_escape_rlike($value)).'[[:>:]]';
                        $parts = "$field RLIKE '$value' AND ($parts)";
                    }
                }
            }
        }
    }

    $sql = "SELECT ".join(',',$this->look_in)." FROM ".$this->table." WHERE $parts";
    //echo testit("search sql",$sql);
    
    global $DB;
    $result = $DB->select("sql:$sql");
    //echo testit('result',$result);
    
    $rows = array();
    /*
    $result = mysql_query($sql);
    if (!$result) {die('Invalid query: ' . mysql_error());} 
    while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
    */

    foreach($result as $row){
        
        $row[score] = 0;
         $score_var = '';
         
        # this is the contence of $ranks_by fields put together to be sent to the scorring machine
        foreach($this->rank_by as $field){
            $score_var .= "$row[$field] ";
            $row['score_var'] .= $score_var;
        }        
        
        # for each word in keywords check how may times it occurs in score_var and add that number to $row[score]
        # TODO
        # This is a place where waiting for multi word matches could be added 
        
        foreach($this->keywords_array as $keyword){
            $row['score'] += preg_match_all("~$keyword~i", $score_var, $null);
        }

        //echo testit('$keyword',$keyword);
        //echo testit('$$score_var',$score_var);
        //echo testit('$row',$row);
        $rows[] = $row;
    }

    
    # if there is a custom sort by colum set, sort by that.
    if($this->sortby){
        $rows = $this->perform_sortby($rows,$this->sortby,$this->sort_order);
    }else{
        # else sort by $row[score] value
        uasort($rows, array($this, "search_sort_results"));
    }
    $this->results = $rows;
    $this->results_count = count($this->results);
    return $rows;
}


function search_rx_escape_terms($keywords_db){
    $out = array();
    foreach($keywords_db as $keyword){
        $out[] = '\b'.preg_quote($keyword, '/').'\b';
    }
    return $out;

}

# compare the $row[score] of $a and $b and output a sort order suggestion for uasort()
function search_sort_results($a, $b){       
    $ax = $a[score];
    $bx = $b[score];

    if ($ax == $bx){ 
        return 0; 
    }
    if($ax > $bx){ 
        return-1; 
    }else{ 
        return 1; 
    }
}

function perform_sortby($array, $on, $order=SORT_ASC){
    // testit("about to perform_sortby with colum =$on  and order = $order",$array);
    
    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }
        
        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
            break;
            case SORT_DESC:
                arsort($sortable_array);
            break;
            case "ASC":
                asort($sortable_array);
            break;
            case "DESC":
                arsort($sortable_array);
            break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }
    // testit("and here it is after perform_sortby $on",$new_array);
    return $new_array;

}
function set_sortby($sortby, $sort_order=SORT_ASC){
    $this->sortby = $sortby;
    if($sort_order){
        $this->sort_order = $sort_order;
    }
}

function search_html_escape_terms(){
    $out = array();
    
    foreach($this->keywords_array as $keyword){
        $keyword = str_replace('%', '*', $keyword);
        if (preg_match("/\s|,/", $keyword)){
            $out[] = '"'.HtmlSpecialChars($keyword).'"';
        }else{
            $out[] = HtmlSpecialChars($keyword);
        }
    }
    return $out;
    
}

function search_pretty_terms($keywords_html){
    if (count($keywords_html) == 1){
        return array_pop($keywords_html);
    }
    $last = array_pop($keywords_html);
    return implode(', ', $keywords_html)." </b>and<b> $last";
}

function show_search_result_block($row){
    # by extending the search class in the instantiating file this method (used in get_results_html) could be over written to customize how results are displayed 
    $img = "";
    if(file_exists($row['image'])){
        if(exif_imagetype($row['image'])){
            $img = "<a href=\"$row[url]\"><img src=\"$row[image]\"></a>";
        }
    }
        
    $block = "
    <div class=\"search_result\">
        $img
        <div>
            <a href=\"$row[url]\">$row[title]</a><br>
            <span>
            $row[description]
            </span>
        </div>
    </div>";

    return $block;
  
}

function get_results_html(){
    $this->search_perform($this->keywords);
    
    $keyword_list = $this->search_pretty_terms($this->search_html_escape_terms($this->search_split_terms($this->keywords)));
    if($keyword_list){ $for_keywords="for <b>$keyword_list</b> ";}
    
    if(count($this->results)){
        $paginate = new paginate($this->results,20);
        $results_paged = $paginate->page_data();
     
        $this->results_message = count($this->results) . " items $for_keywords(".$paginate->x_of_x_pages().") " .$paginate->paging_links();
    
        foreach($results_paged as $key => $value){
            
            
            $results_html .= $this->show_search_result_block($value);
        }

        $results_html .="
            <div align=\"center\">
                <br /><br />
                ".$paginate->x_of_x_pages()."<br />
                ".$paginate->paging_links()."
            </div>";
    }else{
        $this->results_message = "Zero results $for_keywords";
    }
return $results_html;

}
function get_results_message(){
    return $this->results_message;
}
function get_results_count(){
    //echo testit('$this->results_count',$this->results_count);
    return $this->results_count;
}
function get_results_ids(){
    $this->search_perform($this->keywords);
    foreach($this->results as $key => $value){
        # I didn't actually test this after editing :)
        $ids[]=$value[$this->settings['id_field']];
    }
    return $ids;
}
}
?>
