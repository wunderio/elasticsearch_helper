(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.elasticsearchInstantSearch = {
    attach: function (context) {
      var $context = $(context);

      if (drupalSettings.elasticsearchInstantSearch) {
        var searchSource = new Bloodhound({
          identify: function (datum) {
            return datum.uuid;
          },
          queryTokenizer: Bloodhound.tokenizers.whitespace,
          datumTokenizer: Bloodhound.tokenizers.obj.whitespace('label'),
          remote: {
            // Append &rendermode=live to get slower but freshly render search_result markup:
            url: drupalSettings.elasticsearchInstantSearch.remoteSource + '?searchphrase=%QUERY',
            wildcard: '%QUERY'
          },
          rateLimitWait: 150
        });

        var $body = $('body');
        var $instantSearch = $context.find('.instant-search');
        var $instantSearchInput = $instantSearch.find('.instant-search__input');

        var toggleInstantSearchOverlay = function (e) {
          $body.toggleClass('no-scroll');
          $instantSearch.toggleClass('instant-search--open');

          // Empty instant search when opening.
          if ($instantSearch.has('instant-search--open')) {
            $instantSearchInput.typeahead('val', '');
          }

          e.preventDefault();
        };

        // Close instant search via ESC key.
        $(document).on('keyup', function (e) {
          if (e.keyCode === 27 && $instantSearch.hasClass('instant-search--open')) {
            toggleInstantSearchOverlay(e);
          }
        });

        // Type as you go search.
        $(document).on('keypress', function (e) {
          var char = String.fromCharCode(e.which);
          var specialKey = (e.ctrlKey || e.metaKey || e.keyCode === 16 || e.keyCode === 27 || e.keyCode === 9);
          if (!specialKey && document.activeElement.tagName.toLowerCase() === 'body' && !$instantSearch.hasClass('instant-search--open')) {
            toggleInstantSearchOverlay(e);
            $instantSearchInput.val(char).focus();
          }
        });

        // Toggle instant search when clicking trigger or close link.
        $instantSearch.find('.instant-search__trigger, .instant-search__close').on('click', function (e) {
          toggleInstantSearchOverlay(e);
          $instantSearchInput.focus();
        });

        $instantSearchInput.once('elasticsearch-helper-instant')
          .typeahead(null, {
            name: 'search',
            source: searchSource,
            limit: 10,
            display: 'label',
            templates: {
              suggestion: function (datum) {
                return datum.rendered_search_result;
              },
              notFound: function () {
                return Drupal.t('There are no results that match your search');
              }
            }
          })
          // Open result page via Tab oder Enter if there is only one result.
          .on('typeahead:render', function (e, suggestions) {
            if (suggestions.length === 1) {
              $instantSearchInput.on('keydown', function (e) {
                if (e.keyCode === 13 || e.keyCode === 9) {
                  $instantSearchInput.trigger('typeahead:select', suggestions[0]);
                }
              });
            }
          })
          .on('typeahead:select', function (e, suggestion) {
            window.location.href = suggestion.url_internal;
            e.preventDefault();
          })
          // Prevent typeahead from closing.
          .on('typeahead:beforeclose', function (e) {
            e.preventDefault();
          });
      }
    }
  };

})(jQuery, Drupal);

