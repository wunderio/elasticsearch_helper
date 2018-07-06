
(function ($, Drupal) {
  Drupal.behaviors.elasticsearchIndex= {
    attach: function attach() {
      var $configForm = $('#elasticsearch-helper-content-settings-form');
      var inputSelector = 'input[name$="[configurable]"]';

      function toggleTable(checkbox) {
        var $checkbox = $(checkbox);

        $checkbox.closest('.table-language-group').find('table, .tabledrag-toggle-weight').toggle($checkbox.prop('checked'));
      }

      $configForm.once('negotiation-language-admin-bind').on('change', inputSelector, function (event) {
        toggleTable(event.target);
      });

      $configForm.find(inputSelector + ':not(:checked)').each(function (index, element) {
        toggleTable(element);
      });
    }
  };
})(jQuery, Drupal);
