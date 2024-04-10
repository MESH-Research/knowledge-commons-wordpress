(function($) {

  var joinleave_group_change_handler = function() {
    // if the join/leave group button was clicked and ajax call is over (no spinner),
    // refresh the page so that we see the success message & email settings
    if (
      $(this).children().length > 0 &&
      $(this).find('a[class$=-group]').length > 0 &&
      $(this).find('.fa-spin').length === 0
    ) {
      // we really want to see #message, but the top bar covers it so aim a little higher
      window.location.replace(window.location.pathname + window.location.search + '#main');
      // unless we reload, browser simply scrolls up to the anchor.
      // we want to see the email options so refresh everything.
      window.location.reload();
    }
  };

  var getQueryVariable = function(variable) {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i=0;i<vars.length;i++) {
      var pair = vars[i].split("=");
      if(pair[0] == variable){return pair[1];}
    }
    return(false);
  }

  // https://stackoverflow.com/a/12784180
  var getBackgroundImageSize = function(el) {
    var imageUrl = $(el).css('background-image').match(/^url\(["']?(.+?)["']?\)$/);
    var dfd = new $.Deferred();

    if (imageUrl) {
      var image = new Image();
      image.onload = dfd.resolve;
      image.onerror = dfd.reject;
      image.src = imageUrl[1];
    } else {
      dfd.reject();
    }

    return dfd.then(function() {
      return { width: this.width, height: this.height };
    });
  };

  var fixCoverImageDimensions = function() {
    getBackgroundImageSize( $('#header-cover-image')[0] )
      .then( function( size ) {
        if ( 1250 == size.width ) {
          $('#header-cover-image').css({'background-size': 'auto 320px'});
        }
      } );
  }

  $doctable = $( 'table.doctable' );

  $(document).ready(function(){


      // Micromodal
      console.log("Init Micromodal")
      MicroModal.init( {
        openTrigger: 'data-custom-open',
      } );

    if ( $( ".cv p" ).length ) {
      $('.cv #bp-attachment-xprofile-file ').after("<span><button id='delete-upload'>Delete</button></span>");

      $( "#delete-upload" ).on( 'click',  function( e ) {
          e.preventDefault();

         $.ajax({
            method: 'POST',
            url: ajaxurl,
            data: {
                action: 'hc_custom_delete_file',
                file_to_delete: $('.cv p').find('a:first').attr('href'),
                field_id_to_delete: $("input[name=bpaxft_field_id]").val(),
		nonce: settings_general_req.nonce
          },
          }).done(function(data) {

          });

          $('.cv p').remove();
        });
    }


     var url = $(location).attr('href'),
        parts = url.split("/");
        group_slug = parts[4];

    /** 
     * Clear the bp-activity-oldestpage cookie on page load to prevent skipping
     * pages when loading more of an activity feed.
     *
     * The cookie is set in buddyboss - /boss/js/buddyboss.js
     * ```
     * jq.cookie( 'bp-activity-oldestpage', oldest_page, {
     *       path: '/'
     * } );
     * ```
     */
     if( url.indexOf( '/groups/' ) != -1 ) {
      $.removeCookie('bp-activity-oldestpage', { path: '/' } );
     }

    if( url.indexOf( '/groups/' ) != -1 ) {
      document.body.className = document.body.className.replace("activity","home");
    }

    if( url.indexOf( '/documents/' ) != -1 ) {
      $('.group-files-minor-edit').click(function() {

        var current = $(this).data("doc-id");
        var href = $('a[data-doc-id="' + current + '"]');

        if ($(this).is(':checked')) {
            href.attr("href", href.attr("href") + "&action=delete" );
        } else {
            href.attr("href", href.attr("href").replace("&action=delete",""));
        }

      });
    }

    if( url.indexOf( '/admin/group-settings/' ) != -1 ) {

      var group_id = $( "input[name='group-id']" ).val();

      $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: 'generate_menu_options_dropdown',
            group_slug: group_slug,
            group_id: group_id
            },
            success: function (response) {
              $('#group-landing-page-select').html(response);
            }
        });

      $('input[type=radio][id=hide-or-show-menu]').change(function () {
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: 'generate_menu_options_dropdown',
                menu_option_value: $(this).val(),
                menu_option_slug: $(this).data("slug"),
                group_id: group_id,
                group_slug: group_slug
            },
            success: function (response) {
              $('#group-landing-page-select').html('');
              $('#group-landing-page-select').html(response);
            }
        });
      });
    }

    $('body').on('change','select#new-folder-type',function(){
        $select_text = $('select#new-folder-type option:selected').text();
        $('.folder-type-selector-div .buddyboss-select .buddyboss-select-inner span').show();
        $('.folder-type-selector-div .buddyboss-select .buddyboss-select-inner span').text($select_text);
    });

    $('body').on('change','select#new-folder-parent',function(){
        $select_text = $('select#new-folder-parent option:selected').text();
        $( this ).closest('.buddyboss-select-inner').find('span').first().text($select_text);
    });

    /*
     * Expand folders to show contents on click.
     * Contents are fetched via an AJAX request.
     */
    $( '.doctable' ).on( 'click', '.orderby', function( e ) {
      e.preventDefault();


      var classNames = this.className.split(/\s+/);

      var orderby = classNames[1];
      var order = classNames[3];

      switch(order) {
        case 'asc':
           order = 'DESC';
           break;
        case 'desc':
           order = 'ASC';
           break;
          }

      if ( 'modified' == orderby || 'date' == orderby ) {
        $order = 'DESC';
      } else {
        $order = 'ASC';
      }

      var folder_id = $( this ).closest('.toggleable').find('.toggle-folder').first().data('folder-id');

      var container = $( this ).closest( '.toggleable' ).find( '.toggle-content.folder-loop' ).first();

      // Make the AJAX request and populate the list.

      $.ajax( {
        url: ajaxurl,
        type: 'GET',
        data: {
          folder: folder_id,
          group_id: $( '#directory-group-id' ).val(),
          user_id: $( '#directory-user-id' ).val(),
          order: order,
          orderby: orderby,
          action: 'bp_docs_get_folder_content',
        },
        success: function( response ) {
          $( container ).html( '' );
          $( container ).html( response );
          $( '.folder-row-name, .folder-meta-info-statement' ).attr( 'colspan', 10 );
        }

      } );


    } );


    var searchQuery = getQueryVariable('s');
    var searchInput = $('#members_search');

    if ( typeof $.fn.areYouSure === "function" ) {
      $('form#settings-form').areYouSure();
    }

    $(".no-docs").hide();

    if ( $('.create-blog .entry-buddypress-content p a:eq(1)').length ) {
      $('.entry-buddypress-content p a:eq(1)')[0].nextSibling.remove();
    }

    $("#topic-form-toggle").on('click', '#add', function() {
      $(".topic-form").slideToggle("slow");

      $('html,body').animate({
            scrollTop: $(".topic-form").offset().top},
            'slow');
    });

    var previousDate;

    $("#eo-start-date, #eo-end-date").on( 'focus', function() {
      previousDate = $( this ).val(); ;
    } );

    $("#eo-start-date, #eo-end-date").on( 'blur', function() {
        var newDate = $( this ).val();
        if (!moment( newDate, 'dd-mm-yyyy', true ).isValid() ) {
            $( this ).val( previousDate );
        }
    });

    // preserve url searches by copying them to the search box if necessary
    if (searchQuery.length > 0 && searchInput.val() === '') {
      searchInput.val(searchQuery.replace(/\+/g," "));
    }

    if ( $( '#send-to-input').get( 0 ) ) {
      $('#send-to-input').bp_mentions( bp.mentions.users );
    }

    // we need live() to affect pages of groups loaded via ajax.
    $('#groups-dir-list .group-button').on('DOMSubtreeModified', joinleave_group_change_handler);

    // groups directory does not run a new query if "back" button was clicked due to browser cache, so force refresh
    // (without this, results on page can be from the wrong tab despite which is "selected")
    if ($('#members-dir-list, #groups-dir-list').length > 0) {
      $('.item-list-tabs .selected a').trigger('click');
    }

    // disable this since it breaks in safari and isn't really useful anyway
    $.fn.jRMenuMore = function () {}

    $('form#hc-terms-acceptance-form input[type=submit][name=hc_accept_terms_continue]').on('click', function(){
            if ( $('form#hc-terms-acceptance-form input[type=checkbox][name=hc_accept_terms]').is(':checked') ) {
                    $('#hc-terms-acceptance-form').submit();
            } else {
                    alert('Please agree to the terms by checking the box next to "I agree".');
            }
    });

    //this handles the ajax for settings-general.php in single member view
    $('.settings_general_submit input').on('click', function( event ) {

      $.ajax({
        method: 'POST',
        url: ajaxurl,
        data: {
          action: 'hcommons_settings_general',
          nonce: settings_general_req.nonce,
          primary_email: $('.email_selection input[type="radio"]:checked').val()
        },
        cache: false
      }).done(function(data) {

        //store all radio buttons in this var to loop through later
        var radio = $('.email_selection input[type="radio"]');

        //loop through each radio button and whichever one was saved is the one that will be checked.
        radio.each(function( i, v ) {

        //in the context of the current loop
        if( $(this).val() == data.primary_email ) {

          $(this).prop( 'checked', true );
        }

        });

        $('html, body').animate({ scrollTop: 0 }, 'fast');

        //ajax message to assert user that the data has been infact, updated
          $('#item-header-cover').prepend(
            $('<div />', { id: "message", class: "bp-template-notice updated" }).append(
                $('<p />').text('Changed saved.')
              )
            );

      });

      event.preventDefault();

    });


    // admins can add these classes in the widget options, but only to
    // the content of widgets which still leaves an empty box with a
    // border unless we also add the class to the container.
    $( '.hide-if-logged-in.panel-widget-style' ).parent().addClass( 'hide-if-logged-in' );
    $( '.hide-if-logged-out.panel-widget-style' ).parent().addClass( 'hide-if-logged-out' );

    // handle usernames with and without @ in message compose form
    $( '#send_message_form' ).on( 'submit', function( e ) {
      $( '#send-to-input' ).val( $( '#send-to-input' ).val().replace( '@', '' ) );
    } );

    if ( $( '#header-cover-image' ).length ) {
      fixCoverImageDimensions();
    }
  });


})(jQuery);
