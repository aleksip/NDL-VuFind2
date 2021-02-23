/* global VuFind, finna, EasyMDE */
finna.mdEditor = (function finnaMyList() {

  var editorElement = null;
  var container = null;
  var editor = null;
  var preview = null;
  var truncateDone = '<div class="truncate-field" data-rows="1" data-row-height="5" markdown="1"';
  var truncateTag = '<truncate>';
  var truncateCloseTag = '</truncate>';

  function getEditorCursorPos(mdeditor) {
    var doc = mdeditor.codemirror.getDoc();
    var cursorPos = doc.getCursor();
    var position = {
      line: cursorPos.line,
      ch: cursorPos.ch
    };
    return position;
  }

  function insertElement(element, mdeditor) {
    var doc = mdeditor.codemirror.getDoc();
    doc.replaceRange(element, getEditorCursorPos(mdeditor));
    mdeditor.codemirror.focus();
  }

  function toggleTruncateField(mdeditor) {
    var value = mdeditor.value();
    if (value.indexOf(truncateTag) !== -1) {
      return;
    } else {
      var truncateEl = '\n' + truncateTag + '<summary></summary>\n\n' + truncateCloseTag;
      insertElement(truncateEl, mdeditor);
      var doc = editor.codemirror.getDoc();
      var cursorPos = getEditorCursorPos(editor);
      doc.setCursor({line: cursorPos.line - 2, ch: '<truncate><summary>'.length});
    }
  }

  function insertDetails(mdeditor) {
    var summaryPlaceholder = VuFind.translate('details_summary_placeholder');
    var detailsElement = '\n<details class="favorite-list-details" markdown="1">' +
      '<summary markdown="1">' + summaryPlaceholder + '</summary>\n' +
      VuFind.translate('details_text_placeholder') + '\n' +
      '</details>';

    insertElement(detailsElement, mdeditor);
    var doc = editor.codemirror.getDoc();
    var cursorPos = getEditorCursorPos(editor);
    var summaryAndPlaceholder = '<summary>' + summaryPlaceholder;
    doc.setCursor({line: cursorPos.line - 1, ch: summaryAndPlaceholder.length});
  }

  var mdeToolbar = [
    'bold', 'italic',
    'heading', '|',
    'quote', 'unordered-list',
    'ordered-list', '|',
    'link', 'image',
    '|',
    {
      name: 'Details',
      action: function detailsInsert(mdeditor) {
        insertDetails(mdeditor);
      },
      className: 'fa details-icon',
      title: 'Insert details element'
    },
    {
      name: 'truncate',
      action: function truncateFieldToggle(mdeditor) {
        toggleTruncateField(mdeditor);
      },
      className: 'fa fa-pagebreak',
      title: 'Truncate'
    },
    {
      name: 'close',
      action: function closeToolbar() {
        $(document).trigger('click');
      },
      className: 'fa fa-times editor-toolbar-close',
      title: 'Close'
    }
  ];

  function initDetailsElements() {
    $('.favorite-list-details').click(function onDetailsClick() {
      if ($(this).attr('open') === 'open') {
        $(this).attr('open', false);
      } else {
        $(this).attr('open', 'open');
      }
    });
  }

  function handleTruncateField(description, addTruncate) {
    var trunc = typeof addTruncate !== 'undefined' ? addTruncate : true;
    var desc = description;
    var summaryText = '';
    var truncateEl = '';
    var tempDom = '';
    if (trunc && description.indexOf(truncateTag) !== -1) {
      // Fixes preview bug
      desc = desc.replace('<p><truncate>', '<truncate>');

      tempDom = $('<div>').append($.parseHTML(desc));
      // Replace <truncate> with <div class="truncate-field"..>
      truncateEl = $(tempDom).find('truncate');
      truncateEl.wrap(truncateDone + '>');
      var newTruncate = tempDom.find('.truncate-field');
      truncateEl.contents().unwrap();
      newTruncate.find('details').wrap('<div>');

      // Remove <summary> element and add its value to data-label attribute
      if (newTruncate.find(':first-child').is('summary')) {
        summaryText = newTruncate.find(':first-child')[0];
        newTruncate.find(':first-child')[0].remove();
      }
      if (typeof summaryText.innerHTML !== 'undefined') {
        newTruncate.attr('data-label', summaryText.innerHTML);
      }
      desc = tempDom[0].innerHTML;
    } else if (desc.indexOf(truncateDone) !== -1) {
      tempDom = $('<div>').append($.parseHTML(desc));
      // Replace <div class="truncate-field"..> with <truncate> tag and create summary element
      truncateEl = $(tempDom).find('.truncate-field');
      summaryText = truncateEl.attr('data-label');
      if (typeof summaryText === 'undefined') {
        summaryText = '';
      }
      truncateEl.prepend($('<summary>' + summaryText + '</summary>'));
      truncateEl.wrap('<truncate>');
      tempDom.find('.truncate-field details').unwrap();
      tempDom.find('.truncate-field').children().unwrap();
      desc = tempDom[0].innerHTML;
    }
    return desc;
  }

  function initEditableMarkdownField(element) {
    editorElement = element;
    editorElement.toggleClass('edit', true);
    container = editorElement.find('[data-markdown]');

    var textArea = $('<textarea/>');
    var currentVal = null;
    currentVal = container.data('markdown');
    currentVal = handleTruncateField(currentVal, false);
    textArea.text(currentVal);
    container.hide();
    textArea.insertAfter(container);
    if (editor) {
      editor = null;
    }

    var editorSettings = {
      autoDownloadFontAwesome: false,
      autofocus: true,
      element: textArea[0],
      toolbar: mdeToolbar,
      spellChecker: false,
      status: false
    };

    editor = new EasyMDE(editorSettings);
    currentVal = editor.value();

    if (currentVal.indexOf(truncateTag) !== -1) {
      $('.fa-pagebreak').addClass('pagebreak-toggled');
    }
    // Preview
    var html = editor.options.previewRender(editor.value());
    html = handleTruncateField(html);
    $('.markdown-preview').remove();
    preview = $('<div/>').addClass('markdown-preview')
      .html($('<div/>').addClass('data').html(html));
    $('<div/>').addClass('preview').text(VuFind.translate('preview').toUpperCase()).prependTo(preview);
    preview.appendTo(element);
    finna.layout.initTruncate(preview);
    initDetailsElements();

    editor.codemirror.on('change', function onChangeEditor() {
      var result = editor.options.previewRender(editor.value());
      if (result.indexOf(truncateTag) !== -1) {
        if (!$('.fa-pagebreak').hasClass('pagebreak-toggled')) {
          $('.fa-pagebreak').addClass('pagebreak-toggled');
        }
      } else {
        $('.fa-pagebreak').removeClass('pagebreak-toggled');
      }
      result = handleTruncateField(result);
      preview.find('.data').html(result);
      finna.layout.initTruncate(preview);
      initDetailsElements();
    });

    $('.CodeMirror-code').focus();
    // Prevent clicks within the editor area from bubbling up and closing the editor.
    editorElement.closest('.finna-md-editor').unbind('click').click(function onClickEditor() {
      return false;
    });

    return editor;
  }

  function closeEditor() {
    var markdown = editor.value();
    var resultHtml = editor.options.previewRender(markdown)

    editor.toTextArea();
    editor = null;
    editorElement.toggleClass('edit', false).find('textarea').remove();

    container.show();
    container.data('markdown', markdown);
    container.data('empty', markdown.length === 0 ? '1' : '0');
    resultHtml = handleTruncateField(resultHtml);
    container.html(resultHtml);
    finna.layout.initTruncate(container);
    preview.remove();

    return markdown;
  }

  var my = {
    handleTruncateField: handleTruncateField,
    initEditableMarkdownField: initEditableMarkdownField,
    closeEditor: closeEditor
  };

  return my;
})();
