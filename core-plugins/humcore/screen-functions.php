<?php
/**
 * Screen support functions.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * Format book entry input fields.
 */
function format_book_input( $prev_val ) {
?>

	<div id="deposit-book-entries">

		<div id="deposit-book-doi-entry">
			<label for="deposit-book-doi">Publisher DOI</label>
			<input type="text" id="deposit-book-doi" name="deposit-book-doi" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-book-doi'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-doi'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-publisher-entry">
			<label for="deposit-book-publisher">Publisher</label>
			<input type="text" id="deposit-book-publisher" name="deposit-book-publisher" size="40" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-book-publisher'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-publisher'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-publish-date-entry">
			<label for="deposit-book-publish-date">Pub Date</label>
			<input type="text" id="deposit-book-publish-date" name="deposit-book-publish-date" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-book-publish-date'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-publish-date'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-edition-entry">
			<label for="deposit-book-edition">Version</label>
			<input type="text" id="deposit-book-edition" name="deposit-book-edition" size="60" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-book-edition'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-edition'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-volume-entry">
			<label for="deposit-book-volume">Volume</label>
			<input type="text" id="deposit-book-volume" name="deposit-book-volume" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-book-volume'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-volume'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-isbn-entry">
			<label for="deposit-book-isbn">ISBN</label>
			<input type="text" id="deposit-book-isbn" name="deposit-book-isbn" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-book-isbn'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-isbn'] ) . '" '; }
?>
/>
		</div>

	</div>
<?php

}

/**
 * Format book chapter input fields.
 */
function format_book_chapter_input( $prev_val ) {
?>

	<div id="deposit-book-chapter-entries">

		<div id="deposit-book-chapter-doi-entry">
			<label for="deposit-book-chapter-doi">Publisher DOI</label>
			<input type="text" id="deposit-book-chapter-doi" name="deposit-book-chapter-doi" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-book-chapter-doi'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-chapter-doi'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-chapter-publisher-entry">
			<label for="deposit-book-chapter-publisher">Publisher</label>
			<input type="text" id="deposit-book-chapter-publisher" name="deposit-book-chapter-publisher" size="40" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-book-chapter-publisher'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-chapter-publisher'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-chapter-publish-date-entry">
			<label for="deposit-book-chapter-publish-date">Pub Date</label>
			<input type="text" id="deposit-book-chapter-publish-date" name="deposit-book-chapter-publish-date" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-book-chapter-publish-date'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-chapter-publish-date'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-chapter-title-entry">
			<label for="deposit-book-chapter-title">Book Title</label>
			<input type="text" id="deposit-book-chapter-title" name="deposit-book-chapter-title" size="60" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-book-chapter-title'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-chapter-title'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-chapter-author-entry">
			<label for="deposit-book-chapter-author">Book Author or Editor</label>
			<input type="text" id="deposit-book-chapter-author" name="deposit-book-chapter-author" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-book-chapter-author'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-chapter-author'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-chapter-chapter-entry">
			<label for="deposit-book-chapter-chapter">Chapter</label>
			<input type="text" id="deposit-book-chapter-chapter" name="deposit-book-chapter-chapter" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-book-chapter-chapter'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-chapter-chapter'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-chapter-pages-entry">
			<label for="deposit-book-chapter-start-page">Start Page</label>
			<input type="text" id="deposit-book-chapter-start-page" name="deposit-book-chapter-start-page" size="5" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-book-chapter-start-page'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-chapter-start-page'] ) . '" '; }
?>
/>
			<label for="deposit-book-chapter-end-page">End Page</label>
			<input type="text" id="deposit-book-chapter-end-page" name="deposit-book-chapter-end-page" size="5" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-book-chapter-end-page'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-chapter-end-page'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-chapter-isbn-entry">
			<label for="deposit-book-chapter-isbn">ISBN</label>
			<input type="text" id="deposit-book-chapter-isbn" name="deposit-book-chapter-isbn" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-book-chapter-isbn'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-chapter-isbn'] ) . '" '; }
?>
/>
		</div>

	</div>
<?php

}

/**
 * Format book review input fields.
 */
function format_book_review_input( $prev_val ) {
?>

	<div id="deposit-book-review-entries">

		<div id="deposit-book-review-doi-entry">
			<label for="deposit-book-review-doi">Publisher DOI</label>
			<input type="text" id="deposit-book-review-doi" name="deposit-book-review-doi" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-book-review-doi'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-review-doi'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-review-publisher-entry">
			<label for="deposit-book-review-publisher">Publisher</label>
			<input type="text" id="deposit-book-review-publisher" name="deposit-book-review-publisher" size="40" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-book-review-publisher'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-review-publisher'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-review-publish-date-entry">
			<label for="deposit-book-review-publish-date">Pub Date</label>
			<input type="text" id="deposit-book-review-publish-date" name="deposit-book-review-publish-date" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-book-review-publish-date'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-review-publish-date'] ) . '" '; }
?>
/>
		</div>

	</div>
<?php

}

/**
 * Format book section input fields.
 */
function format_book_section_input( $prev_val ) {
?>

	<div id="deposit-book-section-entries">

		<div id="deposit-book-section-doi-entry">
			<label for="deposit-book-section-doi">Publisher DOI</label>
			<input type="text" id="deposit-book-section-doi" name="deposit-book-section-doi" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-book-section-doi'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-section-doi'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-section-publisher-entry">
			<label for="deposit-book-section-publisher">Publisher</label>
			<input type="text" id="deposit-book-section-publisher" name="deposit-book-section-publisher" size="40" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-book-section-publisher'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-section-publisher'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-section-publish-date-entry">
			<label for="deposit-book-section-publish-date">Pub Date</label>
			<input type="text" id="deposit-book-section-publish-date" name="deposit-book-section-publish-date" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-book-section-publish-date'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-section-publish-date'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-section-title-entry">
			<label for="deposit-book-section-title">Book Title</label>
			<input type="text" id="deposit-book-section-title" name="deposit-book-section-title" size="60" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-book-section-title'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-section-title'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-section-author-entry">
			<label for="deposit-book-section-author">Book Author or Editor</label>
			<input type="text" id="deposit-book-section-author" name="deposit-book-section-author" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-book-section-author'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-section-author'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-section-edition-entry">
			<label for="deposit-book-section-edition">Version</label>
			<input type="text" id="deposit-book-section-edition" name="deposit-book-section-edition" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-book-section-edition'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-section-edition'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-section-pages-entry">
			<label for="deposit-book-section-start-page">Start Page</label>
			<input type="text" id="deposit-book-section-start-page" name="deposit-book-section-start-page" size="5" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-book-section-start-page'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-section-start-page'] ) . '" '; }
?>
/>
			<label for="deposit-book-section-end-page">End Page</label>
			<input type="text" id="deposit-book-section-end-page" name="deposit-book-section-end-page" size="5" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-book-section-end-page'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-section-end-page'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-book-section-isbn-entry">
			<label for="deposit-book-section-isbn">ISBN</label>
			<input type="text" id="deposit-book-section-isbn" name="deposit-book-section-isbn" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-book-section-isbn'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-book-section-isbn'] ) . '" '; }
?>
/>
		</div>

	</div>
<?php

}

/**
 * Format journal article input fields.
 */
function format_journal_article_input( $prev_val ) {
?>

	<div id="deposit-journal-entries">

		<div id="deposit-journal-doi-entry">
			<label for="deposit-journal-doi">Publisher DOI</label>
			<input type="text" id="deposit-journal-doi" name="deposit-journal-doi" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-journal-doi'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-journal-doi'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-journal-publisher-entry">
			<label for="deposit-journal-publisher">Publisher</label>
			<input type="text" id="deposit-journal-publisher" name="deposit-journal-publisher" size="40" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-journal-publisher'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-journal-publisher'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-journal-publish-date-entry">
			<label for="deposit-journal-publish-date">Pub Date</label>
			<input type="text" id="deposit-journal-publish-date" name="deposit-journal-publish-date" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-journal-publish-date'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-journal-publish-date'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-journal-title-entry">
			<label for="deposit-journal-title">Journal Title</label>
			<input type="text" id="deposit-journal-title" name="deposit-journal-title" size="75" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-journal-title'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-journal-title'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-journal-volume-entry">
			<label for="deposit-journal-volume"><span>Volume</span>
			<input type="text" id="deposit-journal-volume" name="deposit-journal-volume" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-journal-volume'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-journal-volume'] ) . '" '; }
?>
/>
			</label>
			<label for="deposit-journal-issue"><span>Issue</span>
			<input type="text" id="deposit-journal-issue" name="deposit-journal-issue" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-journal-issue'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-journal-issue'] ) . '" '; }
?>
/>
			</label>
			<br style='clear:both'>
		</div>

		<div id="deposit-journal-pages-entry">
			<label for="deposit-journal-start-page">Start Page</label>
			<input type="text" id="deposit-journal-start-page" name="deposit-journal-start-page" size="5" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-journal-start-page'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-journal-start-page'] ) . '" '; }
?>
/>
			<label for="deposit-journal-end-page">End Page</label>
			<input type="text" id="deposit-journal-end-page" name="deposit-journal-end-page" size="5" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-journal-end-page'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-journal-end-page'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-journal-issn-entry">
			<label for="deposit-journal-issn">ISSN</label>
			<input type="text" id="deposit-journal-issn" name="deposit-journal-issn" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-journal-issn'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-journal-issn'] ) . '" '; }
?>
/>
		</div>

	</div>

<?php

}

/**
 * Format magazine entry input fields.
 */
function format_magazine_section_input( $prev_val ) {
?>

	<div id="deposit-magazine-section-entries">

		<div id="deposit-magazine-section-url-entry">
			<label for="deposit-magazine-section-url">URL</label>
			<input type="text" id="deposit-magazine-section-url" name="deposit-magazine-section-url" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-magazine-section-url'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-magazine-section-url'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-magazine-section-publish-date-entry">
			<label for="deposit-magazine-section-publish-date">Pub Date</label>
			<input type="text" id="deposit-magazine-section-publish-date" name="deposit-magazine-section-publish-date" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-magazine-section-publish-date'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-magazine-section-publish-date'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-magazine-section-title-entry">
			<label for="deposit-magazine-section-title">Magazine</label>
			<input type="text" id="deposit-magazine-section-title" name="deposit-magazine-section-title" size="60" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-magazine-section-title'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-magazine-section-title'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-magazine-section-volume-entry">
			<label for="deposit-magazine-section-volume">Volume</label>
			<input type="text" id="deposit-magazine-section-volume" name="deposit-magazine-section-volume" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-magazine-section-volume'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-magazine-section-volume'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-magazine-section-pages-entry">
			<label for="deposit-magazine-section-start-page">Start Page</label>
			<input type="text" id="deposit-magazine-section-start-page" name="deposit-magazine-section-start-page" size="5" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-magazine-section-start-page'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-magazine-section-start-page'] ) . '" '; }
?>
/>
			<label for="deposit-magazine-section-end-page">End Page</label>
			<input type="text" id="deposit-magazine-section-end-page" name="deposit-magazine-section-end-page" size="5" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-magazine-section-end-page'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-magazine-section-end-page'] ) . '" '; }
?>
/>
		</div>

	</div>
<?php

}

/**
 * Format monograph entry input fields.
 */
function format_monograph_input( $prev_val ) {
?>

	<div id="deposit-monograph-entries">

		<div id="deposit-monograph-doi-entry">
			<label for="deposit-monograph-doi">Publisher DOI</label>
			<input type="text" id="deposit-monograph-doi" name="deposit-monograph-doi" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-monograph-doi'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-monograph-doi'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-monograph-publisher-entry">
			<label for="deposit-monograph-publisher">Publisher</label>
			<input type="text" id="deposit-monograph-publisher" name="deposit-monograph-publisher" size="40" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-monograph-publisher'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-monograph-publisher'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-monograph-publish-date-entry">
			<label for="deposit-monograph-publish-date">Pub Date</label>
			<input type="text" id="deposit-monograph-publish-date" name="deposit-monograph-publish-date" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-monograph-publish-date'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-monograph-publish-date'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-monograph-isbn-entry">
			<label for="deposit-monograph-isbn">ISBN</label>
			<input type="text" id="deposit-monograph-isbn" name="deposit-monograph-isbn" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-monograph-isbn'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-monograph-isbn'] ) . '" '; }
?>
/>
		</div>

	</div>
<?php

}

/**
 * Format newspaper article entry input fields.
 */
function format_newspaper_article_input( $prev_val ) {
?>

	<div id="deposit-newspaper-article-entries">

		<div id="deposit-newspaper-article-url-entry">
			<label for="deposit-newspaper-article-url">URL</label>
			<input type="text" id="deposit-newspaper-article-url" name="deposit-newspaper-article-url" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-newspaper-article-url'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-newspaper-article-url'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-newspaper-article-publish-date-entry">
			<label for="deposit-newspaper-article-publish-date">Pub Date</label>
			<input type="text" id="deposit-newspaper-article-publish-date" name="deposit-newspaper-article-publish-date" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-newspaper-article-publish-date'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-newspaper-article-publish-date'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-newspaper-article-title-entry">
			<label for="deposit-newspaper-article-title">Newspaper</label>
			<input type="text" id="deposit-newspaper-article-title" name="deposit-newspaper-article-title" size="60" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-newspaper-article-title'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-newspaper-article-title'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-newspaper-article-edition-entry">
			<label for="deposit-newspaper-article-edition">Edition</label>
			<input type="text" id="deposit-newspaper-article-edition" name="deposit-newspaper-article-edition" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-newspaper-article-edition'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-newspaper-article-edition'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-newspaper-article-volume-entry">
			<label for="deposit-newspaper-article-volume">Section</label>
			<input type="text" id="deposit-newspaper-article-volume" name="deposit-newspaper-article-volume" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-newspaper-article-volume'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-newspaper-article-volume'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-newspaper-article-pages-entry">
			<label for="deposit-newspaper-article-start-page">Start Page</label>
			<input type="text" id="deposit-newspaper-article-start-page" name="deposit-newspaper-article-start-page" size="5" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-newspaper-article-start-page'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-newspaper-article-start-page'] ) . '" '; }
?>
/>
			<label for="deposit-newspaper-article-end-page">End Page</label>
			<input type="text" id="deposit-newspaper-article-end-page" name="deposit-newspaper-article-end-page" size="5" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-newspaper-article-end-page'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-newspaper-article-end-page'] ) . '" '; }
?>
/>
		</div>

	</div>
<?php

}

/**
 * Format online entry input fields.
 */
function format_online_publication_input( $prev_val ) {
?>

	<div id="deposit-online-publication-entries">

		<div id="deposit-online-publication-url-entry">
			<label for="deposit-online-publication-url">URL</label>
			<input type="text" id="deposit-online-publication-url" name="deposit-online-publication-url" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-online-publication-url'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-online-publication-url'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-online-publication-publisher-entry">
			<label for="deposit-online-publication-publisher">Publisher</label>
			<input type="text" id="deposit-online-publication-publisher" name="deposit-online-publication-publisher" size="40" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-online-publication-publisher'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-online-publication-publisher'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-online-publication-publish-date-entry">
			<label for="deposit-online-publication-publish-date">Pub Date</label>
			<input type="text" id="deposit-online-publication-publish-date" name="deposit-online-publication-publish-date" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-online-publication-publish-date'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-online-publication-publish-date'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-online-publication-title-entry">
			<label for="deposit-online-publication-title">Web site</label>
			<input type="text" id="deposit-online-publication-title" name="deposit-online-publication-title" size="60" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-online-publication-title'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-online-publication-title'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-online-publication-edition-entry">
			<label for="deposit-online-publication-edition">Version</label>
			<input type="text" id="deposit-online-publication-edition" name="deposit-online-publication-edition" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-online-publication-edition'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-online-publication-edition'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-online-publication-volume-entry">
			<label for="deposit-online-publication-volume">Section</label>
			<input type="text" id="deposit-online-publication-volume" name="deposit-online-publication-volume" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-online-publication-volume'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-online-publication-volume'] ) . '" '; }
?>
/>
		</div>

	</div>
<?php

}

/**
 * Format podcastbook entry input fields.
 */
function format_podcast_input( $prev_val ) {
?>

	<div id="deposit-podcast-entries">

		<div id="deposit-podcast-url-entry">
			<label for="deposit-podcast-url">URL</label>
			<input type="text" id="deposit-podcast-url" name="deposit-podcast-url" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-podcast-url'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-podcast-url'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-podcast-publisher-entry">
			<label for="deposit-podcast-publisher">Publisher</label>
			<input type="text" id="deposit-podcast-publisher" name="deposit-podcast-publisher" size="40" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-podcast-publisher'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-podcast-publisher'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-podcast-publish-date-entry">
			<label for="deposit-podcast-publish-date">Pub Date</label>
			<input type="text" id="deposit-podcast-publish-date" name="deposit-podcast-publish-date" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-podcast-publish-date'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-podcast-publish-date'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-podcast-volume-entry">
			<label for="deposit-podcast-volume">Episode</label>
			<input type="text" id="deposit-podcast-volume" name="deposit-podcast-volume" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-podcast-volume'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-podcast-volume'] ) . '" '; }
?>
/>
		</div>

	</div>
<?php

}

/**
 * Format proceedings entry input fields.
 */
function format_proceedings_article_input( $prev_val ) {
?>

	<div id="deposit-proceedings-entries">

		<div id="deposit-proceeding-doi-entry">
			<label for="deposit-proceeding-doi">Publisher DOI</label>
			<input type="text" id="deposit-proceeding-doi" name="deposit-proceeding-doi" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-proceeding-doi'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-proceeding-doi'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-proceeding-publisher-entry">
			<label for="deposit-proceeding-publisher">Publisher</label>
			<input type="text" id="deposit-proceeding-publisher" name="deposit-proceeding-publisher" size="40" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-proceeding-publisher'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-proceeding-publisher'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-proceeding-publish-date-entry">
			<label for="deposit-proceeding-publish-date">Pub Date</label>
			<input type="text" id="deposit-proceeding-publish-date" name="deposit-proceeding-publish-date" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-proceeding-publish-date'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-proceeding-publish-date'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-proceeding-title-entry">
			<label for="deposit-proceeding-title">Proceeding Title</label>
			<input type="text" id="deposit-proceeding-title" name="deposit-proceeding-title" size="75" class="long"
			<?php
			if ( ! empty( $prev_val['deposit-proceeding-title'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-proceeding-title'] ) . '" '; }
?>
/>
		</div>

		<div id="deposit-proceeding-pages-entry">
			<label for="deposit-proceeding-start-page">Start Page</label>
			<input type="text" id="deposit-proceeding-start-page" name="deposit-proceeding-start-page" size="5" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-proceeding-start-page'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-proceeding-start-page'] ) . '" '; }
?>
/>
			<label for="deposit-proceeding-end-page">End Page</label>
			<input type="text" id="deposit-proceeding-end-page" name="deposit-proceeding-end-page" size="5" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-proceeding-end-page'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-proceeding-end-page'] ) . '" '; }
?>
/>
		</div>

	</div>

<?php

}

/**
 * Output book entry display fields.
 */
function humcore_display_book_pub_metadata( $metadata ) {
?>

<dt><?php _e( 'Published as:', 'humcore_domain' ); ?></dt>
<dd><span><?php _e( 'Book', 'humcore_domain' ); // XSS OK. ?></span> &nbsp; &nbsp;
<span class="pub-metadata-display-button button white right">Show details</span>
</dd>
<div class="deposit-item-pub-metadata hide-details">
<?php if ( ! empty( $metadata['doi'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. DOI:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['doi']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['publisher'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Publisher:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['publisher']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['date'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['edition'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Version:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['edition']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['volume'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Volume:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['volume']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['isbn'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'ISBN:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['isbn']; // XSS OK. ?></span></dd>
<?php endif; ?>
</div>
<?php
}

/**
 * Output book chapter display fields.
 */
function humcore_display_book_chapter_pub_metadata( $metadata ) {
?>

<dt><?php _e( 'Published as:', 'humcore_domain' ); ?></dt>
<dd><span><?php _e( 'Book chapter', 'humcore_domain' ); // XSS OK. ?></span> &nbsp; &nbsp;
<span class="pub-metadata-display-button button white right">Show details</span>
</dd>
<div class="deposit-item-pub-metadata hide-details">
<?php if ( ! empty( $metadata['doi'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. DOI:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['doi']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['publisher'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Publisher:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['publisher']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['date'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['book_journal_title'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Book Title:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['book_journal_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['book_author'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Author/Editor:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['book_author']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['chapter'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Chapter:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['chapter']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['start_page'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Page Range:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['start_page'] . ' - ' . $metadata['end_page']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['isbn'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'ISBN:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['isbn']; // XSS OK. ?></span></dd>
<?php endif; ?>
</div>
<?php
}

/**
 * Output book review display fields.
 */
function humcore_display_book_review_pub_metadata( $metadata ) {
?>

<dt><?php _e( 'Published as:', 'humcore_domain' ); ?></dt>
<dd><span><?php _e( 'Book review', 'humcore_domain' ); // XSS OK. ?></span> &nbsp; &nbsp;
<span class="pub-metadata-display-button button white right">Show details</span>
</dd>
<div class="deposit-item-pub-metadata hide-details">
<?php if ( ! empty( $metadata['doi'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. DOI:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['doi']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['publisher'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Publisher:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['publisher']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['date'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['date']; // XSS OK. ?></span></dd>
<?php endif; ?>
</div>
<?php
}

/**
 * Output book section display fields.
 */
function humcore_display_book_section_pub_metadata( $metadata ) {
?>

<dt><?php _e( 'Published as:', 'humcore_domain' ); ?></dt>
<dd><span><?php _e( 'Book section', 'humcore_domain' ); // XSS OK. ?></span> &nbsp; &nbsp;
<span class="pub-metadata-display-button button white right">Show details</span>
</dd>
<div class="deposit-item-pub-metadata hide-details">
<?php if ( ! empty( $metadata['doi'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. DOI:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['doi']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['publisher'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Publisher:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['publisher']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['date'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['book_journal_title'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Book Title:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['book_journal_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['book_author'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Editor(s):', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['book_author']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['edition'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Version:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['edition']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['start_page'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Page Range:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['start_page'] . ' - ' . $metadata['end_page']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['isbn'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'ISBN:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['isbn']; // XSS OK. ?></span></dd>
<?php endif; ?>
</div>
<?php
}

/**
 * Output journal article display fields.
 */
function humcore_display_journal_article_pub_metadata( $metadata ) {
?>

<dt><?php _e( 'Published as:', 'humcore_domain' ); ?></dt>
<dd><span><?php _e( 'Journal article', 'humcore_domain' ); // XSS OK. ?></span> &nbsp; &nbsp;
<span class="pub-metadata-display-button button white right">Show details</span>
</dd>
<div class="deposit-item-pub-metadata hide-details">
<?php if ( ! empty( $metadata['doi'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. DOI:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['doi']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['publisher'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Publisher:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['publisher']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['date'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['book_journal_title'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Journal:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['book_journal_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['volume'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Volume:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['volume']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['issue'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Issue:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['issue']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['start_page'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Page Range:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['start_page'] . ' - ' . $metadata['end_page']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['issn'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'ISSN:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['issn']; // XSS OK. ?></span></dd>
<?php endif; ?>
</div>
<?php
}

/**
 * Output magazine display fields.
 */
function humcore_display_magazine_section_pub_metadata( $metadata ) {
?>

<dt><?php _e( 'Published as:', 'humcore_domain' ); ?></dt>
<dd><span><?php _e( 'Magazine section', 'humcore_domain' ); // XSS OK. ?></span> &nbsp; &nbsp;
<span class="pub-metadata-display-button button white right">Show details</span>
</dd>
<div class="deposit-item-pub-metadata hide-details">
<?php if ( ! empty( $metadata['url'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. URL:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['url']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['date'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['book_journal_title'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Magazine:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['book_journal_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['volume'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Section:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['volume']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['start_page'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Page Range:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['start_page'] . ' - ' . $metadata['end_page']; // XSS OK. ?></span></dd>
<?php endif; ?>
</div>
<?php
}

/**
 * Output monograph display fields.
 */
function humcore_display_monograph_pub_metadata( $metadata ) {
?>

<dt><?php _e( 'Published as:', 'humcore_domain' ); ?></dt>
<dd><span><?php _e( 'Monograph', 'humcore_domain' ); // XSS OK. ?></span> &nbsp; &nbsp;
<span class="pub-metadata-display-button button white right">Show details</span>
</dd>
<div class="deposit-item-pub-metadata hide-details">
<?php if ( ! empty( $metadata['doi'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. DOI:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['doi']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['publisher'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Publisher:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['publisher']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['date'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['isbn'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'ISBN:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['isbn']; // XSS OK. ?></span></dd>
<?php endif; ?>
</div>
<?php
}

/**
 * Output newspaper article display fields.
 */
function humcore_display_newspaper_article_pub_metadata( $metadata ) {
?>

<dt><?php _e( 'Published as:', 'humcore_domain' ); ?></dt>
<dd><span><?php _e( 'Newspaper article', 'humcore_domain' ); // XSS OK. ?></span> &nbsp; &nbsp;
<span class="pub-metadata-display-button button white right">Show details</span>
</dd>
<div class="deposit-item-pub-metadata hide-details">
<?php if ( ! empty( $metadata['url'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. URL:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['url']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['date'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['book_journal_title'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Newspaper:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['book_journal_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['edition'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Edition:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['edition']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['volume'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Section:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['volume']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['start_page'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Page Range:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['start_page'] . ' - ' . $metadata['end_page']; // XSS OK. ?></span></dd>
<?php endif; ?>
</div>
<?php
}

/**
 * Output online display fields.
 */
function humcore_display_online_publication_pub_metadata( $metadata ) {
?>

<dt><?php _e( 'Published as:', 'humcore_domain' ); ?></dt>
<dd><span><?php _e( 'Online publication', 'humcore_domain' ); // XSS OK. ?></span> &nbsp; &nbsp;
<span class="pub-metadata-display-button button white right">Show details</span>
</dd>
<div class="deposit-item-pub-metadata hide-details">
<?php if ( ! empty( $metadata['url'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. URL:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['url']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['publisher'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Publisher:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['publisher']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['date'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['book_journal_title'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Website:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['book_journal_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['edition'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Version:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['edition']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['volume'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Section:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['volume']; // XSS OK. ?></span></dd>
<?php endif; ?>
</div>
<?php
}

/**
 * Output podcast display fields.
 */
function humcore_display_podcast_pub_metadata( $metadata ) {
?>

<dt><?php _e( 'Published as:', 'humcore_domain' ); ?></dt>
<dd><span><?php _e( 'Podcast', 'humcore_domain' ); // XSS OK. ?></span> &nbsp; &nbsp;
<span class="pub-metadata-display-button button white right">Show details</span>
</dd>
<div class="deposit-item-pub-metadata hide-details">
<?php if ( ! empty( $metadata['url'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. URL:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['url']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['publisher'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Publisher:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['publisher']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['date'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['volume'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Episode:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['volume']; // XSS OK. ?></span></dd>
<?php endif; ?>
</div>
<?php
}

/**
 * Output proceedings display fields.
 */
function humcore_display_proceedings_article_pub_metadata( $metadata ) {
?>

<dt><?php _e( 'Published as:', 'humcore_domain' ); ?></dt>
<dd><span><?php _e( 'Conference proceeding', 'humcore_domain' ); // XSS OK. ?></span> &nbsp; &nbsp;
<span class="pub-metadata-display-button button white right">Show details</span>
</dd>
<div class="deposit-item-pub-metadata hide-details">
<?php if ( ! empty( $metadata['doi'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. DOI:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['doi']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['publisher'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Publisher:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['publisher']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['date'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Pub. Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['book_journal_title'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Proceeding:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['book_journal_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['start_page'] ) || humcore_is_deposit_item_review() ) : ?>
<dt><?php _e( 'Page Range:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['start_page'] . ' - ' . $metadata['end_page']; // XSS OK. ?></span></dd>
<?php endif; ?>
</div>
<?php
}

/**
 * Output non-published  display fields.
 */
function humcore_display_non_published_metadata( $metadata ) {
?>

<dt><?php _e( 'Published?', 'humcore_domain' ); ?></dt>
<dd><span><?php _e( 'No', 'humcore_domain' ); // XSS OK. ?></span></dd>
<dt><?php _e( 'Creation Date:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['date'] ) ) : ?>
<dd><?php echo esc_html( $metadata['date'] ); ?></dd>
<?php else : ?>
<dd>( None entered )</dd>
<?php
endif;
}

/**
 * Output deposit resourceembed
 */
function humcore_embed_resource( $deposit_url, $file_metadata = array(), $width = "60%", $height = "400px;" ) {

	preg_match( '/^(\/deposits\/item)\/([^\/]+)\/?$/', $deposit_url, $url_matches );
	$deposit_pid = $url_matches[2];

	if ( empty( $file_metadata ) ) {
        	$deposit_id = $deposit_pid;
        	$item_found = humcore_has_deposits( 'include=' . $deposit_id );
        	humcore_the_deposit();
        	$record_identifier = humcore_get_deposit_record_identifier();
        	$record_location   = explode( '-', $record_identifier );
        	// handle legacy MLA Commons value
        	if ( $record_location[0] === $record_identifier ) {
                	$record_location[0] = '1';
                	$record_location[1] = $record_identifier;
        	}
                $switched = false;
        	if ( get_current_blog_id() != $record_location[0] ) {
                	switch_to_blog( $record_location[0] );
                	$switched = true;
        	}

        	$post_data     = get_post( $record_location[1] );
        	$file_metadata = json_decode( get_post_meta( $record_location[1], '_deposit_file_metadata', true ), true );
	}

        if ( $switched ) {
                restore_current_blog();
        }

	if ( empty( $file_metadata ) ) {
		return;
	}

	$site_url = get_option( 'siteurl' );
        $view_url = sprintf(
                '%1$s/deposits/objects/%2$s/datastreams/CONTENT/content',
                $site_url,
                $file_metadata['files'][0]['pid']
        );

        if ( in_array( $file_metadata['files'][0]['filetype'], array( 'application/pdf', 'text/html', 'text/plain' ) ) ) { 
		$embed = sprintf(
			'<iframe width="%s" height="%s" src="%s/app/plugins/humcore/pdf-viewer/web/viewer.html?file=%s&download=false&print=false&openfile=false"></iframe>',
			$width,
			$height,
			$site_url,
			urlencode( $view_url )
		);
        } else if ( in_array( strstr( $file_metadata['files'][0]['filetype'], '/', true ), array( 'audio', 'image', 'video' ) ) ) {
                $embed = sprintf(
                        '<iframe width="%s" height="%s" src="%s"></iframe>',
			$width,
			$height,
                        $view_url
                );
        } else {
                $embed = sprintf(
			'<iframe src="https://docs.google.com/viewer?url=%s&embedded=true" style="width:%s height:%s" frameborder="0"></iframe>',
                        $view_url,
			$width,
			$height
                );
        }

	echo $embed;
	return;

}
