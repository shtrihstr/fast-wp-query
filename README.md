#Fast WP_Query
WP_Query MySQL optimization by using object cache

##Benchmark
###1. Select last 10 posts (7000 rows in db)   
```php
$query = new WP_Query( [ 'post_type' => 'post', 'posts_per_page' => 10 ] );
```
####Default
SQL:   
```sql
SELECT SQL_CALC_FOUND_ROWS wp_posts.ID FROM wp_posts WHERE 1=1 AND wp_posts.post_type = 'post' AND (wp_posts.post_status = 'publish')  ORDER BY wp_posts.post_date DESC LIMIT 0, 10;
``` 
Query time: **31.588 ms**

####With plugin
SQL:   
```sql
SELECT wp_posts.ID FROM wp_posts  WHERE 1=1  AND wp_posts.post_type = 'post' AND (wp_posts.post_status = 'publish')  ORDER BY wp_posts.post_date DESC LIMIT 0, 10;
```  
Query time: **7.869 ms**

###2. Select random 10 posts (7000 rows in db)   
```php
$query = new WP_Query( [ 'post_type' => 'post', 'orderby' => 'rand', 'posts_per_page' => 10 ] );
```
####Default
SQL:   
```sql
SELECT SQL_CALC_FOUND_ROWS wp_posts.ID FROM wp_6_posts WHERE 1=1 AND wp_posts.post_type = 'post' AND (wp_posts.post_status = 'publish') ORDER BY RAND() LIMIT 0, 10;
SELECT FOUND_ROWS();
SELECT wp_6_posts.* FROM wp_posts WHERE ID IN (9424,4699,834,9064,10472,284,9078,8091,2062);
```
Query time: **33.383 ms**

####With plugin
SQL:   
```sql
SELECT wp_posts.ID FROM wp_posts WHERE 1=1 AND wp_posts.ID IN (10337,10336,9863,9814,10433,10414,9906,9989,9664,9599) AND wp_posts.post_type = 'post' AND (wp_posts.post_status = 'publish') ORDER BY FIELD( wp_posts.ID, 10337,10336,9863,9814,10433,10414,9906,9989,9664,9599 ) LIMIT 0, 10;
SELECT wp_posts.* FROM wp_posts WHERE ID IN (9863,9814,10433,10414,9906,9989,9664,9599);
```
Query time: **10.413 ms**
