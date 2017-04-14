# PHP + MySql website site search class

A simple configurable site search class. Sarch a MySql table of pages, images, or other site content and return matching results sorted by relevance, rating and or other criteria.

Supports quoted phrase matching and wild-card search characters as well as stripping common filler words and optional custom sorting and/or required conditions.

Uses a customizable version of the short stopwords list from http://www.ranks.nl/stopwords

Take a look at search-example.php for an example implementation.

The code in search.class.php is also heavily commented with a lot of typos and notes to my self about what is going on.

Currently depends on the photosynthesis database and pagenate classes and testit function. None of the above would be hard to remove or swap out.
