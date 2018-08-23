# PHP + MySql website site search class

A simple configurable class to sarch a MySql table of pages, images, or other site content and return matching results sorted by relevance, rating and or other criteria.

Supports quoted phrase matching and wild-card search characters as well as stripping common filler words and optional custom sorting and/or required conditions.

Uses a customizable version of the short stopwords list from http://www.ranks.nl/stopwords

## How To Use It
### In your config.php or init.php file globally set search setttings
    // The SQL table to search
    search::$search_settings['table'] = 'your_table_name';
    // Array of DB field names to look in
    search::$search_settings['look_in'] = array('title','description','keywords');
    // Array of DB filed names, in which matches should effect the rank of result relevence
    search::$search_settings['rank_by'] = array('title','description','keywords');
    
    # Additional Optional Settings
    // Set to the uneque id field of table
    search::$search_settings['id_field'] = 'page_id';
    // Field with rating
    search::$search_settings['rating_field'] = 'user_rat';
    //custom stop words 
    search::$search_settings['common_words'] = array('image','photo','picture');
    
### Anywhere you need to show search results
    // Create the search object.
    $search = new search($keyword);
    
    #Return results formated as html
    $results_html = $search->get_results_html();

Take a look at search-example.php for a detailed example implementation.

The code in search.class.php is also heavily commented with a lot of typos and notes to my self about what is going on.

## Dependencies
This class currently depends on the photosynthesis https://github.com/Photosynthesis database and pagenate classes and testit function. None of the above would be too hard to remove or swap out though...

## To Do
Figgure out a good lightweight method to match plural and singular versions of words.
