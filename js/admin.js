/* globals jQuery, date_range_filter */
jQuery(function($) {

	var intervals = {
		init: function( $wrapper ) {
			this.wrapper = $wrapper;
			this.save_interval( this.wrapper.find( '.button-primary' ), this.wrapper );

			this.$ = this.wrapper.each( function( i, val ) {
				var container   = $( val ),
						dateinputs  = container.find( '.date-inputs' ),
						from        = container.find( '.field-from' ),
						to          = container.find( '.field-to' ),
						to_remove   = to.prev( '.date-remove' ),
						from_remove = from.prev( '.date-remove' ),
						predefined  = container.children( '.field-predefined' ),
						datepickers = $( '' ).add( to ).add( from );

				if ( jQuery.datepicker ) {

					// Apply a GMT offset due to Date() using the visitor's local time
					var	siteGMTOffsetHours  = parseFloat( date_range_filter.gmt_offset ),
							localGMTOffsetHours = new Date().getTimezoneOffset() / 60 * -1,
							totalGMTOffsetHours = siteGMTOffsetHours - localGMTOffsetHours,
							localTime           = new Date(),
							siteTime            = new Date( localTime.getTime() + ( totalGMTOffsetHours * 60 * 60 * 1000 ) ),
							dayOffset           = '0';

					// check if the site date is different from the local date, and set a day offset
					if ( localTime.getDate() !== siteTime.getDate() || localTime.getMonth() !== siteTime.getMonth() ) {
						if ( localTime.getTime() < siteTime.getTime() ) {
							dayOffset = '+1d';
						} else {
							dayOffset = '-1d';
						}
					}

					if ( undefined !== jQuery.fn.select2 ) {
						predefined.select2();
					}

					datepickers.datepicker({
						dateFormat: 'yy/mm/dd',
						maxDate: dayOffset,
						defaultDate: siteTime
					});

					datepickers.datepicker( 'widget' ).addClass( 'date-range-filter-datepicker' );
				}

				if ( '' !== from.val() ) {
					from_remove.show();
				}

				if ( '' !== to.val() ) {
					to_remove.show();
				}

				predefined.on({
					'change': function () {
						var value    = $( this ).val(),
								option   = predefined.find( '[value="' + value + '"]' ),
								to_val   = option.data( 'to' ),
								from_val = option.data( 'from' );

						if ( 'custom' === value ) {
							dateinputs.show();
							return false;
						} else {
							dateinputs.hide();
							datepickers.datepicker( 'hide' );
						}

						from.val( from_val ).trigger( 'change', [ true ] );
						to.val( to_val ).trigger( 'change', [ true ] );

						if ( jQuery.datepicker && datepickers.datepicker( 'widget' ).is( ':visible' ) ) {
							datepickers.datepicker( 'refresh' ).datepicker( 'hide' );
						}
					},
					'check_options': function () {
						if ( '' !== to.val() && '' !== from.val() ) {
							var	option = predefined
									.find( 'option' )
									.filter( '[data-to="' + to.val() + '"]' )
									.filter( '[data-from="' + from.val() + '"]' );
							if ( 0 !== option.length ) {
								predefined.val( option.attr( 'value' ) ).trigger( 'change', [ true ] );
							} else {
								predefined.val( 'custom' ).trigger( 'change', [ true ] );
							}
						} else if ( '' === to.val() && '' === from.val() ) {
							predefined.val( '' ).trigger( 'change', [ true ] );
						} else {
							predefined.val( 'custom' ).trigger( 'change', [ true ] );
						}
					}
				});

				from.on( 'change', function() {
					if ( '' !== from.val() ) {
						from_remove.show();
						to.datepicker( 'option', 'minDate', from.val() );
					} else {
						from_remove.hide();
					}

					if ( true === arguments[ arguments.length - 1 ] ) {
						return false;
					}

					predefined.trigger( 'check_options' );
				});

				to.on( 'change', function() {
					if ( '' !== to.val() ) {
						to_remove.show();
						from.datepicker( 'option', 'maxDate', to.val() );
					} else {
						to_remove.hide();
					}

					if ( true === arguments[ arguments.length - 1 ] ) {
						return false;
					}

					predefined.trigger( 'check_options' );
				});

				// Trigger change on load
				predefined.trigger( 'change' );

				$( '' ).add( from_remove ).add( to_remove ).on( 'click', function() {
					$( this ).next( 'input' ).val( '' ).trigger( 'change' );
				});
			});
		},

		save_interval: function( $btn ) {
			var $wrapper = this.wrapper;
			$btn.click( function() {
				var data = {
					key:   $wrapper.find( 'select.field-predefined' ).find( ':selected' ).val(),
					start: $wrapper.find( '.date-inputs .field-from' ).val(),
					end:   $wrapper.find( '.date-inputs .field-to' ).val()
				};

				// Add params to URL
				$( this ).attr( 'href', $( this ).attr( 'href' ) + '&' + $.param( data ) );
			});
		},

		init_dashboard: function( $obj ) {
			// Addind listeners
			$obj.on('click', function() {
				var $this = $(this);
				$this.children('span').toggleClass('dashicons-arrow-right dashicons-arrow-down');
				$this.children('ul').toggle();
			});
		}
	};

	$( document ).ready( function() {
		intervals.init( $( '#date-range-filter-interval' ) );
		intervals.init_dashboard( $('#date_range_dashboard_widget div.inside .drf-dashboard-table td.manage-column:first-child') );
	});
});
