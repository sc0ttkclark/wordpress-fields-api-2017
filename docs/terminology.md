# Fields API Terminology

## Object Types

Object Types are types of objects in WordPress, but can also represent custom objects from plugins or themes.

See [Object Types and Names](https://github.com/sc0ttkclark/wordpress-fields-api/blob/master/docs/object-types-and-names.md) for more details.

## Object Subtypes

Object Subtypes are names of subsets of data, like Post Types, Taxonomies, or Comment Types. 

See [Object Types and Names](https://github.com/sc0ttkclark/wordpress-fields-api/blob/master/docs/object-types-and-names.md) for more details

## Fields

Fields handle getting and saving of values and should be namespaced properly (`{my_}field_name`) for your project. They should be unique for the object type and Object subtype you use it on, otherwise it will be overwritten each time a duplicate one is added.

See [Registering Fields](https://github.com/sc0ttkclark/wordpress-fields-api/blob/master/docs/registering-fields.md) for more details and examples.