!function($) {
  var pasteCatcher = document.createElement("div")
    , is_registered = false;
  // Firefox allows images to be pasted into contenteditable elements
  if (!window.Clipboard) {
    pasteCatcher.setAttribute("contenteditable", "");

    // We can hide the element and append it to the body,
    pasteCatcher.style.opacity = 0;
    pasteCatcher.style.height = 0;
    pasteCatcher.style.width = 0;
    pasteCatcher.style.position = 'absolute';
    $(function() {
      document.body.appendChild(pasteCatcher);
    });
  }

  $.fn.pasteCatcher = function() {
    if (!window.Clipboard) {
      // pasteCatcher.focus();
      $(this).on('click', function() {
        // as long as we make sure it is always in focus
        $(pasteCatcher).css({
          top : $(document).scrollTop()
        }).focus();
      });
    }

    // Add the paste event listener
    if (!is_registered) {
      $(window).on("paste", pasteHandler);
      is_registered = true;
    }
  };

  /* Handle paste events */
  function pasteHandler(ev) {
    var e = ev.originalEvent;
    // We need to check if event.clipboardData is supported (Chrome)
    if (e.clipboardData) {
      // Get the items from the clipboard
      var items = e.clipboardData.items;
      if (items) {
        // Loop through all items, looking for any kind of image
        for (var i = 0; i < items.length; i++) {
          if (items[i] && items[i].type.indexOf("image") !== -1) {
            // We need to represent the image as a file,
            var blob = items[i].getAsFile();
            // and use a URL or webkitURL (whichever is available to the browser)
            // to create a temporary URL to the object
            var URLObj = window.URL || window.webkitURL;
            var source = URLObj.createObjectURL(blob);

            // The URL can then be used as the source of an image
            createImage(source);
          }
        }
      }
    // If we can't handle clipboard data directly (Firefox),
    // we need to read what was pasted from the contenteditable element
    } else {
      // pasteCatcher.focus();
      $(pasteCatcher).css({
        top : $(document).scrollTop()
      }).focus();
      // This is a cheap trick to make sure we read the data
      // AFTER it has been inserted.
      setTimeout(checkInput, 1);
    }
  }

  /* Parse the input in the paste catcher element */
  function checkInput() {
    // Store the pasted content in a variable
    var child = pasteCatcher.childNodes[0];

    // Clear the inner html to make sure we're always
    // getting the latest inserted content
    pasteCatcher.innerHTML = "";

    if (child) {
      // If the user pastes an image, the src attribute
      // will represent the image as a base64 encoded string.
      if (child.tagName === "IMG") {
          createImage(child.src);
      }
    }
  }

  function createImage(source) {
    // Creates a new image from a given source
    var pastedImage = new Image();
    pastedImage.onload = function() {
      // You now have the image!
    }
    pastedImage.src = source;
    $(window).trigger('pasteCatcher', [pastedImage, source]);
  }
}(jQuery);

