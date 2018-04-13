(function (Drupal) {

  "use strict";

  /**
   * Ajax 'elasticsearch_query_debug' command: prints Elasticsearch query to console for debuggin.
   *
   * @param {Drupal.Ajax} ajax
   *   An Ajax object.
   * @param {object} response
   *   The Ajax response.
   * @param {string} response.data
   *    The Ajax response's content.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.elasticsearch_query_debug = function (ajax, response, status) {
    if (console && console.log) {
      var json = (typeof response.text == 'object' ? response.text : JSON.parse(response.text));
      console.log(JSON.stringify(json, null, 2));
    }
  };

})(Drupal);
