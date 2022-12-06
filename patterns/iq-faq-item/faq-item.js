(function ($, Drupal) {
  setListenerForScrollOffsetCalculation('scroll, resize');
  function generateAccordion($stacks) {
    var definition = {
      icons: false,
      heightStyle: 'content',
      collapsible: true,
      active: false,
      activate: function( event, ui ) {
        if(!$.isEmptyObject(ui.newHeader.offset())) {
          $('html:not(:animated), body:not(:animated)').animate({ scrollTop: ui.newHeader.offset().top - window.scrollOffset }, 'slow');
        }
      }
    };
    var $first = null;
    var newGroup = true;
    var type = '';
    $stacks.each(function (i, stack) {
      $stack = $(stack);
      if ($stack.hasClass('iq-processed-faq-item')) {
        return;
      }
      $stack.addClass('iq-processed-faq-item');

      if (newGroup) {
        if ($first != null) {
          $first.accordion(definition);
        }
        $first = $stack;
        if (!$stack.next().hasClass('iq-faq-item')) {
          newGroup = true;
        } else {
          newGroup = false;
        }
      } else {
        $first.append($stack.find('.iq-faq-item-question')).append($stack.children('.iq-faq-item-answer'));
        if (!$stack.next().hasClass('iq-faq-item')) {
          newGroup = true;
        }
        $stack.remove();
      }
    });
    if ($first != null) {
      $first.accordion(definition);
    }
  }

  function rearrangeViewItems($stacks) {
    $stacks.each(function (i, stack) {
      $stack = $(stack);
      let $parent = $stack.parent();
      $stack.closest('.view-iq-faq .view-content').append(stack);
      $parent.remove();
    });
  }

  Drupal.behaviors.iq_faq = {
    attach: function (context, settings) {
      rearrangeViewItems($(context).find('.view-iq-faq [data-faq-accordion]'));
      generateAccordion($(context).find('[data-faq-accordion]'));
    }
  };

})(jQuery, Drupal);
