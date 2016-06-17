# php-mysql-search-class
A php + MySql website site search class with tunable result ranking as well as optional custom sort and required conditions.

Currently depends on the PSC database and pagenate classes and testit function but none of the above would be hard to remove or swap out.

A nice feature to add would be a page rank sort weighting method that could skew the results towards items that had a high rating. (this could be implemented by multiplying the store_by property by a rank property from the database.)

