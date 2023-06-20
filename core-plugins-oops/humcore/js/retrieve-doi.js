// A script to retrieve a DOI's metadata from CrossRef for CORE
// Copyright (c) Martin Paul Eve 2015
// Released under the MIT license
// Uses a component from DOI Regex by Richard Littauer (https://github.com/regexps/doi-regex) under an MIT license


 jQuery(document).ready(function($)
    {
        // Inject the DOI lookup field
        var element = $('#deposit-title-entry');
        var content = $('<div id="lookup-doi-entry"><label for="lookup-doi">Retrieve journal article metadata from DOI (optional)</label><input type="text" id="lookup-doi" name="lookup-doi" class="long" value="" /> <button onClick="javascript:retrieveDOI(); return false;">Retrieve</button> <span style="color:red" id="lookup-doi-message"></span></div>');
	// not used
        // content.insertBefore(element);
    });

 function returnJSON(response, element)
    {
      // Return an element from the JSON or a blank
      // if there is no such element
      try
      {
        return response[element];
      }
      catch (err)
      {
        return "";
      }
    }

 function testDOI(DOI, DOIregex)
    {
      // Check if a string is a valid DOI
      DOI = DOI || {};
      matcher = DOI.exact ? new RegExp('^' + DOIregex + '$') : new RegExp(DOIregex, 'g');
      return matcher.exec(DOI);
    }

 function retrieveDOI()
    {
      // Lookup a DOI and fill in the fields for the user
      // Journals only at the moment
      $ = jQuery;
      var response = '';
      var DOI = $('#lookup-doi').val();
      var url = 'https://api.crossref.org/works/' + DOI;
      var DOIregex = '(10[.][0-9]{4,}(?:[.][0-9]+)*/(?:(?![%"#? ])\\S)+)';
      var message = $('#lookup-doi-message');

      if (testDOI(DOI, DOIregex) == null)
      {
        message.text('Please enter a valid DOI.');
        resetFields();
        return false;
      }

      // Use Yahoo! pipes for this request to circumvent
      // same-origin policy. An alternative would be to
      // write our own server-side proxy.
      // Now using CORS.

      message.text('Retrieving information.');

      $.ajax({
          type: "GET",
          //accepts: "application/vnd.citationstyles.csl+json",
          url: url,
//          async: false,
          crossDomain: true,
          dataType: 'json',
          error: function (data)
          {
            if ( data.status == 404 )
            { 
              message.text('That DOI was not found in CrossRef.');
            }
            else
            {
              message.text(data.responseText);
            }
            console.dir(data);
            resetFields();
            return false;
          },
          success: function (data)
          {
            // Make sure we haven't changed type
            resetFields();

            // parse the received JSON
            var deposittype = returnJSON(data.message, "type");
            if (deposittype != 'book' && deposittype != 'book-chapter' && deposittype != 'book-section' && deposittype != 'journal-article' && deposittype != 'monograph' && deposittype != 'proceedings-article')
            {
              message.text('Sorry, we only support information retrieval for book, book chapter, book section, conference proceeding, journal article and monograph at this time.');
              return false;
            }

            var title = returnJSON(data.message, "title");
            var containertitle = returnJSON(data.message, "container-title");
            var subject = returnJSON(data.message, "subject");
            var pages = returnJSON(data.message, "page");

            if (pages == '' || pages == null)
            { 
              pages = ['',''];
            }
            else if (pages.indexOf('-') == -1) 
            {
              pages = ['','']; 
            }
            else
            {
              pages = pages.split('-'); 
            }

            var DOIUrl = returnJSON(data.message, "URL");
            var publisher = returnJSON(data.message, "publisher");
            var issn = returnJSON(data.message, "ISSN");

            if (typeof(issn) == 'Array')
            {
              // Multiple ISSNs can be returned so here we take the first if it's an array
              issn = issn[0];
            }

            //var edition = returnJSON(data.message, "edition");
            var volume = returnJSON(data.message, "volume");
            var issue = returnJSON(data.message, "issue");
            var createddate = data.message["created"]["date-parts"][0];

            var chapter = returnJSON(data.message, "chapter");
            var crossref_isbn = returnJSON(data.message, "ISBN");

            if (typeof crossref_isbn !== "undefined" && crossref_isbn != '')
            {
              // Multiple ISBNs can be returned so here we take the first if it's an array
              var first_isbn = crossref_isbn[0];
              // Parse isbn, we don't want the full url.
              var isbn_regex = /^(?:http.+?isbn\/|)(.+?)$/;
              var isbn_matches = first_isbn.match(isbn_regex);
              if (typeof isbn_matches !== "undefined" && isbn_matches != '')
              {
                var isbn = isbn_matches[1];
              }
              else
              {
                var isbn = first_isbn;
              }
            }
            else
            {
              var isbn = '';
            }

            var author = returnJSON(data.message, "author");
            var authors = [];
            if (typeof author !== "undefined" && author != '')
            {
              $.each( author, function( i, val )
              {
                authors.push( val["given"] + ' ' + val["family"] );
              });
            }

            var editor = returnJSON(data.message, "editor");
            var editors = [];
            if (typeof editor !== "undefined" && editor != '')
            {
              $.each( editor, function( i, val )
              {
                editors.push( val["given"] + ' ' + val["family"] );
              });
            }

            var author_editor = authors.join(", ");
            if (author_editor == '' || author_editor == null)
            {
              author_editor = editors.join(", ");
            }

            $('#deposit-title-unchanged').val(title);

            if (deposittype == 'book')
            {
              // update "Item Type"
              $('#deposit-genre').val("Book").trigger("change");

              // update published item type
              $('input[type="radio"][name="deposit-publication-type"][value="book"]').prop('checked', true);
              $('input[type="radio"][name="deposit-publication-type"][value="book"]').click();
                
              // update book fields
              $('#deposit-book-doi').val(DOI);
              $('#deposit-book-publisher').val(publisher);
              $('#deposit-book-title').val(containertitle);
              $('#deposit-book-author').val(author_editor);
              $('#deposit-book-isbn').val(isbn);
              //$('#deposit-book-edition').val(edition);
              $('#deposit-book-volume').val(volume);
              if (createddate != null)
              {
                $('#deposit-book-publish-date').val(createddate[0] + "-" + createddate[1] + "-" + createddate[2]);
              }
              message.text('We found information for that book! You can review it before submitting your deposit.');
            }
            else if (deposittype == 'book-chapter')
            {
              // update "Item Type"
              $('#deposit-genre').val("Book chapter").trigger("change");

              // update published item type
              $('input[type="radio"][name="deposit-publication-type"][value="book-chapter"]').prop('checked', true);
              $('input[type="radio"][name="deposit-publication-type"][value="book-chapter"]').click();
                
              // update book chapter fields
              $('#deposit-book-chapter-doi').val(DOI);
              $('#deposit-book-chapter-publisher').val(publisher);
              $('#deposit-book-chapter-title').val(containertitle);
              $('#deposit-book-chapter-author').val(author_editor);
              $('#deposit-book-chapter-isbn').val(isbn);
              $('#deposit-book-chapter-chapter').val(chapter);
              $('#deposit-book-chapter-start-page').val(pages[0]);
              $('#deposit-book-chapter-end-page').val(pages[1]);
              if (createddate != null)
              {
                $('#deposit-book-chapter-publish-date').val(createddate[0] + "-" + createddate[1] + "-" + createddate[2]);
              }
              message.text('We found information for that book chapter! You can review it before submitting your deposit.');
            }
            else if (deposittype == 'book-section')
            {
              // update "Item Type"
              $('#deposit-genre').val("Book section").trigger("change");

              // update published item type
              $('input[type="radio"][name="deposit-publication-type"][value="book-section"]').prop('checked', true);
              $('input[type="radio"][name="deposit-publication-type"][value="book-section"]').click();
                
              // update book section fields
              $('#deposit-book-section-doi').val(DOI);
              $('#deposit-book-section-publisher').val(publisher);
              $('#deposit-book-section-title').val(containertitle);
              $('#deposit-book-section-author').val(author_editor);
              //$('#deposit-book-section-edition').val(edition);
              $('#deposit-book-section-isbn').val(isbn);
              $('#deposit-book-section-start-page').val(pages[0]);
              $('#deposit-book-section-end-page').val(pages[1]);
              if (createddate != null)
              {
                $('#deposit-book-section-publish-date').val(createddate[0] + "-" + createddate[1] + "-" + createddate[2]);
              }
              message.text('We found information for that book section! You can review it before submitting your deposit.');
            }
            else if (deposittype == 'journal-article')
            {
              // update "Item Type"
              $('#deposit-genre').val("Article").trigger("change");

              // update published item type
              $('input[type="radio"][name="deposit-publication-type"][value="journal-article"]').prop('checked', true);
              $('input[type="radio"][name="deposit-publication-type"][value="journal-article"]').click();
                
              // update journal fields
              $('#deposit-journal-doi').val(DOI);
              $('#deposit-journal-publisher').val(publisher);
              $('#deposit-journal-title').val(containertitle);
              $('#deposit-journal-issn').val(issn);
              $('#deposit-journal-volume').val(volume);
              $('#deposit-journal-issue').val(issue);
              $('#deposit-journal-start-page').val(pages[0]);
              $('#deposit-journal-end-page').val(pages[1]);
              if (createddate != null)
              {
                $('#deposit-journal-publish-date').val(createddate[0] + "-" + createddate[1] + "-" + createddate[2]);
              }
              message.text('We found information for that journal article! You can review it before submitting your deposit.');
            }
            else if (deposittype == 'monograph')
            {
              // update "Item Type"
              $('#deposit-genre').val("Monograph").trigger("change");

              // update published item type
              $('input[type="radio"][name="deposit-publication-type"][value="monograph"]').prop('checked', true);
              $('input[type="radio"][name="deposit-publication-type"][value="monograph"]').click();
                
              // update monograph fields
              $('#deposit-monograph-doi').val(DOI);
              $('#deposit-monograph-publisher').val(publisher);
              $('#deposit-monograph-title').val(containertitle);
              $('#deposit-monograph-isbn').val(isbn);
              if (createddate != null)
              {
                $('#deposit-monograph-publish-date').val(createddate[0] + "-" + createddate[1] + "-" + createddate[2]);
              }
              message.text('We found information for that monograph! You can review it before submitting your deposit.');
            }
            else if (deposittype == 'proceedings-article')
            {
              // update "Item Type" and also its visible rendering
              $('#deposit-genre').val("Conference proceeding").trigger("change");

              // update published item type
              $('input[type="radio"][name="deposit-publication-type"][value="proceedings-article"]').prop('checked', true);
              $('input[type="radio"][name="deposit-publication-type"][value="proceedings-article"]').click();
                
              // update conference proceeding fields
              $('#deposit-proceeding-doi').val(DOI);
              $('#deposit-proceeding-publisher').val(publisher);
              $('#deposit-proceeding-title').val(containertitle);
              $('#deposit-proceeding-start-page').val(pages[0]);
              $('#deposit-proceeding-end-page').val(pages[1]);
              if (createddate != null)
              {
                $('#deposit-proceeding-publish-date').val(createddate[0] + "-" + createddate[1] + "-" + createddate[2]);
              }
              message.text('We found information for that conference proceeding! You can review it before submitting your deposit.');
            }
        }
      });

    }

 function resetFields()
    {
      $('#deposit-title-unchanged').val("");

      // update "Item Type"
      $('#deposit-genre').val("").trigger("change");

      // update published item type
      $('input[type="radio"][name="deposit-publication-type"]:checked').prop('checked', false);
      $('input[type="radio"][name="deposit-publication-type"][value="none"]').prop('checked', true);
//      $('input[type="radio"][name="deposit-publication-type"][value="none"]').click();

      // update book fields
      $('#deposit-book-doi').val("");
      $('#deposit-book-publisher').val("");
      $('#deposit-book-title').val("");
      $('#deposit-book-isbn').val("");
      $('#deposit-book-edition').val("");
      $('#deposit-book-volume').val("");
      $('#deposit-book-publish-date').val("");

      // update book chapter fields
      $('#deposit-book-chapter-doi').val("");
      $('#deposit-book-chapter-publisher').val("");
      $('#deposit-book-chapter-title').val("");
      $('#deposit-book-chapter-author').val("");
      $('#deposit-book-chapter-isbn').val("");
      $('#deposit-book-chapter-chapter').val("");
      $('#deposit-book-chapter-start-page').val("");
      $('#deposit-book-chapter-end-page').val("");
      $('#deposit-book-chapter-publish-date').val("");

      // update book section fields
      $('#deposit-book-section-doi').val("");
      $('#deposit-book-section-publisher').val("");
      $('#deposit-book-section-title').val("");
      $('#deposit-book-section-author').val("");
      $('#deposit-book-section-isbn').val("");
      $('#deposit-book-section-edition').val("");
      $('#deposit-book-section-start-page').val("");
      $('#deposit-book-section-end-page').val("");
      $('#deposit-book-section-publish-date').val("");

      // update conference proceeding fields
      $('#deposit-proceeding-doi').val("");
      $('#deposit-proceeding-publisher').val("");
      $('#deposit-proceeding-title').val("");
      $('#deposit-proceeding-start-page').val("");
      $('#deposit-proceeding-end-page').val("");
      $('#deposit-proceeding-publish-date').val("");

      // update journal fields
      $('#deposit-journal-doi').val("");
      $('#deposit-journal-publisher').val("");
      $('#deposit-journal-title').val("");
      $('#deposit-journal-issn').val("");
      $('#deposit-journal-volume').val("");
      $('#deposit-journal-issue').val("");
      $('#deposit-journal-start-page').val("");
      $('#deposit-journal-end-page').val("");
      $('#deposit-journal-publish-date').val("");

      // update monograph fields
      $('#deposit-monograph-doi').val("");
      $('#deposit-monograph-publisher').val("");
      $('#deposit-monograph-title').val("");
      $('#deposit-monograph-publish-date').val("");
      $('#deposit-monograph-isbn').val("");

    }
