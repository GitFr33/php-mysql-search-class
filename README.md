#PHP + MySql website site search class
A php class to search in a MySql table of pages, images, or other site content an return matching results ranked by relevance. Supports google style quoted phrase matching and wildcard charicters as well as stripping common filler words and optional custom sorting and required conditions.

Take a look at search-example.php for a very commented example implimentation.

The core code in search.class.php is also pretty heavily commented with a lot of typos and notes to my self about what is going on.

Currently depends on the PSC database and pagenate classes and testit function but none of the above would be hard to remove or swap out.

A nice feature to add would be a page rank sort weighting method that could skew the results towards items that had a high rating. (This could be implemented by multiplying the score_by property by a rank property from the database.)
