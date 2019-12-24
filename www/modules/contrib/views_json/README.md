Views JSON
==========

This module provides a set of plugins for Views to render content in JSON. This
format allows content in a Backdrop site to be easily used as a data source for
Semantic Web clients or web mash-ups.

A JSON data document will render the nodes generated by a view as a
serialization of an array of Javascript objects with each object's properties
corresponding to a view field.

JSON data documents are available in plain JSON or any of the following formats:

 * Simple JSON - just plain-vanilla JSON serialization
 * Simile/Exhibit JSON - the serialization format used by the Exhibit web
   app - http://simile.mit.edu/exhibit/
 * JqGrid


Installation
------------

 - Install this module using the official Backdrop CMS instructions at
   https://backdropcms.org/guide/modules.

 - In the Views UI set the view style (in Format section) to JSON data document
   to render as Simple JSON or Simile/Exhibit JSON.

 - In the view format settings choose the options or vocabulary for your format.

 - Add the fields to your view that contain the information you want to be
   pulled into the format renderer. All formats will output the fields
   recognized as belonging to that format.

 - That's it! The rendered view will be visible in the preview and at your
   view's page display path. When you create a page display for your view with
   a unique URL, no markup is emitted from this page, just the data for the
   particular content type with the proper Content-Type HTTP header.

 Documentation
 -------------

 Additional documentation is located in the Wiki:
 https://github.com/backdrop-contrib/views_json/wiki/Documentation.

 Issues
 ------

 Bugs and Feature requests should be reported in the Issue Queue:
 https://github.com/backdrop-contrib/views_json/issues.

 Current Maintainers
 -------------------

 - Jen Lampton (https://github.com/jenlampton)
 - Seeking additional maintainers

 Credits
 -------

 - Ported to Backdrop CMS by [Jen Lampton](https://github.com/jenlampton).
 - Originally written for Drupal by [allisterbeharry](https://www.drupal.org/user/116802).
 - Maintained for Drupal by [many wonderful people](https://www.drupal.org/node/260895/committers).

 License
 -------

 This project is GPL v2 software. See the LICENSE.txt file in this directory for
 complete text.