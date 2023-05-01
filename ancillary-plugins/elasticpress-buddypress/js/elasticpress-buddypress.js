window.elasticPressBuddyPress = {

  // markup for various UI elements
  loaderDiv: '<div class="epbp-loader"><img src="/app/plugins/elasticpress-buddypress/img/ajax-loader.gif"></div>',
  noMoreResultsDiv: '<div class="epbp-msg no-more-results">No more results.</div>',
  noResultsDiv: '<div class="epbp-msg no-results">No results.</div>',
  errorDiv: '<div class="epbp-msg error">Something went wrong! Please try a different query.</div>',

  // are we presently awaiting results?
  loading: false,

  // request in progress, if any
  xhr: null,

  // what page of results are we loading?
  page: 1,

  // are we retrieving future-dated posts?
  future: false,

  // element to which results are appended ( set in init() since it doesn't exist until document.ready )
  target: null,

  // helper function to customize jQuery.tabselect initialization for multiselect search facets
  initTabSelect: function( formElement, targetElement ) {
    var tabElements = [];
    var selectedTabs = [];

    $( formElement ).children().each( function( i, el ) {
      tabElements.push( el.innerHTML );

      if ( $( el ).prop( 'selected' ) ) {
        selectedTabs.push( el.innerHTML );
      }
    } );

    $( targetElement ).tabSelect( {
      tabElements: tabElements,
      selectedTabs: selectedTabs,
      formElement: formElement,
      onChange: function( selected ) {
        // select & deselect options
        $( formElement ).children().each( function() {
          // use .attr() rather than .prop() so that serializeArray() finds these elements.
          $( this ).attr( 'selected', ( $.inArray( this.innerHTML, selected ) !== -1 ) );
        } );

        // trigger handleFacetChange
        $( formElement ).trigger( 'change' );
      }
    } );
  },

  // add links to select all for multiselect facet
  initSelectAll: function( selectId ) {
    var header = $( '.ep-bp-search-facets' ).find( '[for=' + selectId + ']' );
    var selectAllLink = $( '<a>Select all</a>' );

    selectAllLink
      .appendTo( header )
      .on( 'click', elasticPressBuddyPress.handleSelectAllClick );
  },

  // handle "select all" clicks
  handleSelectAllClick: function( e ) {
    // TODO would be nice not to rely on such an elaborate selector
    var tabSelectContainer = $( this ).parent().parent().next( 'select[multiple]' ).next();

    $.each( tabSelectContainer.children(), function( i, tab ) {
      if ( $( tab ).hasClass( 'inactive' ) ) {
        $( tab ).trigger( 'click' );
      }
    } );

    e.preventDefault();
  },

  // show loading indicators and clear existing results if necessary
  showLoading: function() {
    elasticPressBuddyPress.clearLoading();

    elasticPressBuddyPress.loading = true;

    if ( elasticPressBuddyPress.page > 1 ) {
      elasticPressBuddyPress.target.append( elasticPressBuddyPress.loaderDiv );
    } else {
      $( '.ep-bp-search-facets' ).append( elasticPressBuddyPress.loaderDiv );
      elasticPressBuddyPress.target.addClass( 'in-progress' );
    }
  },

  // remove loading indicators
  clearLoading: function() {
    elasticPressBuddyPress.loading = false;

    elasticPressBuddyPress.target.removeClass( 'in-progress' );

    $( '.epbp-loader' ).remove();
  },

  // change handler for search facets
  handleFacetChange: function() {
    elasticPressBuddyPress.page = 1;
    elasticPressBuddyPress.loadResults();
  },

  // "change" (really, keyup) handler for search input
  handleSearchInputChange: function() {
    // only process change if the value of the input actually changed (not some other key press)
    if ( $( '#s' ).val() !== $( '#ep-bp-facets [name=s]' ).val() ) {
      $( '#ep-bp-facets [name=s]' ).val( $( '#s' ).val() );
      elasticPressBuddyPress.page = 1;
      elasticPressBuddyPress.loadResults();
    }
  },

  // infinite scroll
  handleScroll: function() {
    // No infinite scroll on mobile.
    if ( $( 'body' ).hasClass( 'is-mobile' ) ) {
      return;
    }

    // No infinite scroll unless explicitly enabled.
    if ( ! elasticPressBuddyPress.infiniteScrollEnabled ) {
      return;
    }

    var targetScrollTop =
      elasticPressBuddyPress.target.offset().top +
      elasticPressBuddyPress.target.outerHeight() -
      window.innerHeight * 5;

    if(
      ! elasticPressBuddyPress.target.children( '.epbp-msg' ).length &&
      ! elasticPressBuddyPress.loading &&
      ( $( window ).scrollTop() >= targetScrollTop || elasticPressBuddyPress.target.children().length < 10 )
    ) {
      elasticPressBuddyPress.page++;
      elasticPressBuddyPress.loadResults();
    }
  },

  // form submission handler. everything loads via xhr, so just prevent default handler.
  handleSubmit: function( e ) {
    e.preventDefault();
  },

  // initiate a new xhr to fetch results, then render them (or an appropriate message if no results)
  loadResults: function() {
    var handleSuccess = function( data ) {
      // clear existing results unless we're infinite scrolling
      if ( elasticPressBuddyPress.page === 1 ) {
        elasticPressBuddyPress.target.html( '' );
        window.scrollTo( 0, 0 );
      } else {
        $('.btn.more').remove();
      }

      if ( data.posts.length ) {
        // remove results which are already listed on other network(s)
        // this is done serverside too but only affects one page at a time
        // doing it again here prevents dupes when they appear on different pages
        $( data.posts ).each( function( i, thisPost ) {
          $( elasticPressBuddyPress.target.children( 'article' ) ).each( function( j, thatPost ) {
            if (
              $( thisPost ).attr( 'id' ).split('-')[1] === $( thatPost ).attr( 'id' ).split('-')[1] &&
              $( thisPost ).find( '.entry-title' ).text() === $( thatPost ).find( '.entry-title' ).text()
            ) {
              delete data.posts[i];
            }
          } );
        } );

        elasticPressBuddyPress.page += data.pages;
        elasticPressBuddyPress.future = data.future;

        elasticPressBuddyPress.target.append( data.posts.join( '' ) );

        $( '<a href="#" class="btn more">More results</a>' )
          .appendTo( elasticPressBuddyPress.target )
          .on( 'click', function( e ) {
            e.preventDefault();
            elasticPressBuddyPress.loadResults();
          } );
      } else {
        if ( elasticPressBuddyPress.page > 1 ) {
          elasticPressBuddyPress.target.append( elasticPressBuddyPress.noMoreResultsDiv );
        } else {
          elasticPressBuddyPress.target.append( elasticPressBuddyPress.noResultsDiv );
        }
      }
    }
    var handleError = function( request, textStatus, error ) {
      var err = textStatus + ", " + error;
      console.log( "Request Failed: " + err );
      if ( request.statusText !== 'abort' ) {
        elasticPressBuddyPress.target.html( elasticPressBuddyPress.errorDiv );
      }
    }
    var handleAlways = function( data, textStatus, errorThrown ) {
      if ( textStatus !== 'abort' ) {
        elasticPressBuddyPress.clearLoading();
      }

      if ( typeof data.debug !== 'undefined' && typeof data.debug.ep_query !== 'undefined' ) {
        console.log( 'ElasticSearch response: ' + data.debug.ep_query.request.response.code + ' ' + data.debug.ep_query.request.response.message );
      }

      // keep loading more results to account for first pages with fewer than 10 results
      $( window ).trigger( 'scroll' );
    }
    var serializedFacets = ( function() {
      /*
      var post_type_facets = $('#post_type').serializeArray();
      var network_facets = $('#index').serializeArray();
      
      var parsedFacets = [].concat( post_type_facets, network_facets );
      */
      
      var parsedFacets = $( '.ep-bp-search-facets' ).serializeArray();

      parsedFacets.push( {
        name: 'paged',
        value: elasticPressBuddyPress.page
      } );

      parsedFacets.push( {
        name: 'future',
        value: elasticPressBuddyPress.future
      } );

      for ( var i = 0; i < parsedFacets.length; i++ ) {
        parsedFacets[ i ].value = $.trim( parsedFacets[ i ].value );
      }

      return $.param( parsedFacets );
    } )();

    elasticPressBuddyPress.showLoading();

    // abort pending request, if any, before starting a new one
    if ( elasticPressBuddyPress.xhr && 'abort' in elasticPressBuddyPress.xhr ) {
      elasticPressBuddyPress.xhr.abort();
    }

    // TODO set ajax path with wp_localize_script() from EPR_REST_Posts_Controller property
    elasticPressBuddyPress.xhr = $.getJSON( '/wp-json/epr/v1/query?' + serializedFacets )
      .success( handleSuccess )
      .fail( handleError )
      .always( handleAlways );
  },

  // automatically hide & show relevant order options
  updateOrderSelect: function() {
    // makes no sense to offer the option to sort least relevant results first,
    // so hide order when sorting by score.
    // boss adds markup to all selects so we must hide those too for now.
    if ( $( '#orderby' ).val() === '_score' ) {

      // in case user had selected asc, reset
      if ( $( '#order' ).val() !== 'desc' ) {
        $( '#order [value="asc"]' ).attr( 'selected', false );
        $( '#order [value="desc"]' ).attr( 'selected', true );
        $( this ).trigger( 'change' );
      }

      $( '#order' ).hide(); // theme-independent, hopefully
      $( '#order' ).parents( '.buddyboss-select' ).css( 'opacity', 0 ); // boss

    } else {

      $( '#order' ).show();
      $( '#order' ).parents( '.buddyboss-select' ).css( 'opacity', 1 );

    }
  },

  // combine topic & reply facets to simplify UX
  combineDiscussionTypeFacets: function() {
    var realFacetButtons = $( '#ep_bp_post_type_facet' ).children( ':contains(Topics), :contains(Replies)' );
    var fakeFacetButton = $( '<span>Discussions</span>' );

    // if the real facets have different active states, i.e. topics=active & replies=inactive,
    // something is wrong or maybe someone is being clever manually changing the filters/url.
    // either way, we can't combine them without changing the results, so just abort
    if ( $( realFacetButtons[0] ).attr( 'class' ) !== $( realFacetButtons[1] ).attr( 'class' ) ) {
      return;
    }

    realFacetButtons.hide();

    fakeFacetButton
      .addClass( realFacetButtons.attr( 'class' ) ) // only uses the class of the first button
      .appendTo( '#ep_bp_post_type_facet' )
      .on( 'click', function() {
        if ( fakeFacetButton.hasClass( 'active' ) ) {
          fakeFacetButton.removeClass( 'active' ).addClass( 'inactive' );
        } else {
          fakeFacetButton.removeClass( 'inactive' ).addClass( 'active' );
        }
        realFacetButtons.trigger( 'click' );
      } );
  },

  // set up tabselect, event handlers, etc.
  init: function() {
    // trigger the #orderby change handler once boss Selects have initialized
    var observer = new MutationObserver( function() {
      if ( $( '.ep-bp-search-facets' ).children( '.buddyboss-select' ).length && $( '#orderby' ).val() === '_score' ) {
        elasticPressBuddyPress.updateOrderSelect();

        // mobile search input
        $( '.is-mobile .ep-bp-search-facets [name=s]' ).attr( 'type', 'text' );

        observer.disconnect();
      }
    } );

    observer.observe( $( '.ep-bp-search-facets' )[0], { childList: true } );

    elasticPressBuddyPress.target = $( '#content' );

    elasticPressBuddyPress.initTabSelect( '#post_type', '#ep_bp_post_type_facet' );
    elasticPressBuddyPress.initTabSelect( '#index', '#ep_bp_index_facet' );

    elasticPressBuddyPress.initSelectAll( 'post_type' );
    elasticPressBuddyPress.initSelectAll( 'index' );

    elasticPressBuddyPress.combineDiscussionTypeFacets();

    // Disable infinite scroll by default.
    elasticPressBuddyPress.infiniteScrollEnabled = false;

    // ensure consistent search input values
    $( '#s' ).val( $.trim( $( '#ep-bp-facets [name=s]' ).val() ) );

    // event handlers
    $( '#ep-bp-facets' ).find( 'select' ).on( 'change', elasticPressBuddyPress.handleFacetChange );
    $( '#ep-bp-facets' ).find( 'input' ).on( 'keyup', elasticPressBuddyPress.handleFacetChange );
    $( '#orderby' ).on( 'change', elasticPressBuddyPress.updateOrderSelect );
    $( '#s' ).on( 'keyup', elasticPressBuddyPress.handleSearchInputChange );
    $( window ).on( 'scroll', elasticPressBuddyPress.handleScroll );
    $( '#searchform, #ep-bp-facets' ).on( 'submit', elasticPressBuddyPress.handleSubmit );

    elasticPressBuddyPress.loadResults();
  }

}

jQuery( document ).ready( elasticPressBuddyPress.init );
