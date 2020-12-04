$(document).ready(function onFinnaTabsNavReady() {
  $('.finna-tabs-nav').each(function doFinnaTabsNavLayout() {
    var activeUl = $(this).find('.finna-nav > li.active > ul');
    if (activeUl.length > 0) {
      $(this).css('height', $(this).height() + activeUl.height());
    }
  });
});
