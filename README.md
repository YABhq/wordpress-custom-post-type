# Custom Post Type Class

Creating custom post types has never been easier using the Custom Post Type class.

Let's create a custom post type for cars. First we have to import the class into our namespace.
```php
use Yab\CustomPostType;
```

Pass the singular name to the constructor, and it will automatically be pluralized for you.
```php
$car = new CustomPostType('Car');
```

We also have full control and can pass any options available in the [WordPress Codex](https://codex.wordpress.org/Function_Reference/register_post_type)

If we want to specify our plural name, we can pass it as the second parameter.

Let's create another post type for our inventory. We can pass it the plural name, and an array of options to override the defaults.
```php
$inventory = new CustomPostType('Inventory', 'Inventories', [
    'menu_icon' => 'dashicons-performance',
    'hierarchical' => true,
    'supports' => ['title', 'editor', 'thumbnail', 'revisions', 'page-attributes']
]);
```

### Add Taxonomies

Adding taxonomies is just as easy. We just extend our object we just created.
```php
$inventory->add_taxonomy('Warehouse Section');
$inventory->add_taxonomy('Inventory Category', 'Inventory Categories');
$inventory->add_taxonomy('Inventory Category', 'Inventory Categories', ['hierarchical' => true]);
```

### Custom Meta Boxes FTW

Adding meta boxes will full control over type and style is very easy.
```php
$inventory->add_meta_box(
    'Inventory Details',
    [
        ['name' => 'Retail Price', 'class' => 'half-width'],
        ['name' => 'Weekly Price', 'class' => 'half-width'],
        ['name' => 'Boat Only Price', 'class' => 'half-width'],
        ['name' => 'Included Motor & Trailer']
    ],
    'side',
    'default'
);
```
