(function(window) {
  var is_logged_in = false

  // makes our security check read-only keeping it uneditable in dev console.
  function SecurityCheck() { }

  SecurityCheck.prototype.is_logged_in = function() {
    return is_logged_in;
  };

  SecurityCheck.prototype.login = function(value) {
    is_logged_in = value;
  };

  window.SecurityCheck = SecurityCheck

  jQuery(document).ready(function($) {
    window._wq = window._wq || [];
    var user = new SecurityCheck();

    _wq.push({ id: "_all", onReady: function(video) {
      /**
       * Watch the seconds
       */
      video.bind("secondchange", function() {
        // this should be another call to really valid login
        if (video.secondsWatched() >= 60 && !user.is_logged_in() ) {
          video.pause().time(59);
          $('#loginModal').modal('show')
        }
      });
    }});

    $('form#login').on('submit', function(e){
      $('form#login p.status').show().text( ajax_login_object.loadingmessage );
      $.ajax({
        type: 'POST',
        dataType: 'json',
        url: ajax_login_object.ajaxurl,
        data: {
          'action': 'ajaxlogin',
          'username': $('form#login #username').val(),
          'password': $('form#login #password').val(),
          'security': $('form#login #security').val() },
        success: function(data){
          $('form#login p.status').html( data.message );
          user.login( data.loggedin )
          if( data.loggedin ){
            jQuery('#loginModal').modal('hide')
          }
        },
      });
      e.preventDefault();
    });
  });
})(window);
