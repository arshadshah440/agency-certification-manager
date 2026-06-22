const NM_DASHBOARD_STATE_KEY = "nm_dashboard_state";
function isNMFilterRestore() {
	const params = new URLSearchParams(window.location.search);
	return params.get("restore") === "true";
}
function isNMDashboardPage() {
	return window.location.pathname.includes("dashboard");
}
function isNMDashboardDetailPage() {
	return window.location.pathname.includes("entry-details");
}

jQuery(document).ready(function ($) {
	// dow
	if (isNMFilterRestore()) {
		const restored = restoreNMDashboardState();
	} else {
		if (isNMDashboardPage()) {
			sessionStorage.removeItem(NM_DASHBOARD_STATE_KEY);
		}
	}
	if (isNMDashboardDetailPage()) {
		var restoreapp = sessionStorage.getItem("agency_detail_current_app");
		var agency_details_current_agency = sessionStorage.getItem(
			"agency_details_current_agency",
		);
		var currentagency_id = getUrlParameter("agency-id");
		if (restoreapp) {
			if (agency_details_current_agency == currentagency_id) {
				filtersEntriesNM(restoreapp);
			}
		}
	}
	// If no saved state, load default
	// if (!restored) {
	// 	filtersDashboardNM();
	// }

	jQuery(".nm-entries-wrapper li a").on("click", function (e) {
		e.preventDefault();

		var $link = jQuery(this);
		var currententry_id = $link.attr("data-entry");
		var agency_id = $link.attr("data-agency");
		var redirectUrl = $link.attr("href");

		jQuery.ajax({
			url: nm_ajax_obj.ajaxurl,
			type: "POST",
			data: {
				action: "nm_change_entry_status",
				security: nm_ajax_obj.nonce,
				currententry_id: currententry_id,
			},
			beforeSend() {
				$link.prop("disabled", true);
			},
			success(res) {
				if (res.data.status === true) {
					sessionStorage.setItem("agency_detail_current_app", currententry_id);
					sessionStorage.setItem("agency_details_current_agency", agency_id);
					window.location.href = redirectUrl;
				} else {
					alert("Something went wrong, kindly try again.");
				}
			},
			error() {
				alert("Something went wrong, kindly try again.");
			},
			complete() {
				$link.prop("disabled", false);
			},
		});
	});

	jQuery("#nm_search_input").on("input", function () {
		const length = jQuery(this).val().length;

		if (length === 0 || length > 3) {
			filtersDashboardNM();
		}
	});

	jQuery("#nm_entry_details_wrapper").on(
		"change",
		"input[name='toggle_entry_status_opre']",
		function (e) {
			e.preventDefault();
			var entry_status = jQuery(this).val();
			var entry_id = jQuery("#opre_id_status_entry").attr("entry-id");
			console.log("console");
			jQuery.ajax({
				url: nm_ajax_obj.ajaxurl,
				type: "POST",
				data: {
					action: "nm_opre_status",
					security: nm_ajax_obj.nonce,
					entry_id: entry_id,
					entry_status: entry_status,
				},
				beforeSend() {
					$("#opre_id_status_entry label").prop("disabled", true);
				},
				success(res) {
					window.location.reload();
				},
				complete() {
					$("#opre_id_status_entry label").prop("disabled", false);
				},
			});
		},
	);

	jQuery("#nm_entry_details_wrapper").on(
		"click",
		"#nm_save_letter",
		function (e) {
			e.preventDefault();
			var letter_mockup = jQuery("#letter_preview_wrapper").html();
			var entry_id = jQuery(this).attr("entry-id");
			var toggle_entry_status = jQuery(this).attr("entry-status");

			jQuery.ajax({
				url: nm_ajax_obj.ajaxurl,
				type: "POST",
				data: {
					action: "nm_save_letter_callback",
					security: nm_ajax_obj.nonce,
					entry_status: toggle_entry_status,
					entry_id: entry_id,
					letter_mockup: letter_mockup,
				},
				beforeSend() {
					$("#nm_save_letter").prop("disabled", true);
				},
				success(res) {
					console.log(res);
					alert(res.data.message);
					if (jQuery("#nm_action_after_letter").length) {
						if (res.data.attachment_detail && res.data.attachment_detail.url) {
							jQuery("#nm_download_pdf").attr("href", res.data.attachment_detail.url);
						}
						jQuery("#nm_action_after_letter").show();
					} else {
						window.location.reload();
					}
				},
				complete() {
					$("#nm_save_letter").prop("disabled", false);
				},
			});
		},
	);

	jQuery("#nm_entry_details_wrapper").on(
		"click",
		"#nm_view_whole_entry",
		function (e) {
			e.preventDefault();
			var entry_id = jQuery(this).attr("entry-details-id");

			jQuery.ajax({
				url: nm_ajax_obj.ajaxurl,
				type: "POST",
				data: {
					action: "nm_view_entry_details",
					security: nm_ajax_obj.nonce,
					entry_id: entry_id,
				},
				beforeSend() {
					$("#nm_view_whole_entry").prop("disabled", true);
				},
				success(res) {
					if (res.data.pdf_url) {
						// Create a temporary link and trigger download
						const link = document.createElement("a");
						link.href = res.data.pdf_url;
						link.target = "_blank"; // open in new tab
						link.rel = "noopener noreferrer"; // security best practice
						document.body.appendChild(link);
						link.click();
						document.body.removeChild(link);
					}
				},
				complete() {
					$("#nm_view_whole_entry").prop("disabled", false);
				},
			});
		},
	);

	jQuery("#nm_entry_details_wrapper").on(
		"change",
		"input[name='toggle_entry_status']",
		function (e) {
			e.preventDefault();
			if ($(this).val() == "deny") {
				jQuery("#nm_approval_fields").hide();
				jQuery("#deny_fields_nm").show();
			} else {
				jQuery("#nm_approval_fields").show();
				jQuery("#deny_fields_nm").hide();
			}
		},
	);

	jQuery("#nm_entry_details_wrapper").on(
		"click",
		".nm_email_letter_btns",
		function (e) {
			e.preventDefault();
			let currentUser = jQuery(this).attr("currentuseremail");
			let toUser = jQuery(this).attr("touseremail");

			if (jQuery(this).hasClass("nm-yes-send-to-provider")) {
				// YES & EMAIL LETTER TO PROVIDER → show Form 56
				jQuery("#nm_letter_creation_form_53").addClass("hide_me_form_ar");
				jQuery("#nm_letter_creation_form_56").removeClass("hide_me_form_ar");
				jQuery("#nm_letter_creation_form_56")
					.find(".frm_to_user_input input[type='email']")
					.val(currentUser);
				jQuery("#nm_letter_creation_form_56")
					.find(".nm_to_email_input input[type='email']")
					.val(toUser);
			} else {
				// All other buttons (NO / EMAIL TO SUPERVISOR / EMAIL TO COORDINATOR) → show Form 53
				jQuery("#nm_letter_creation_form_56").addClass("hide_me_form_ar");
				jQuery("#nm_letter_creation_form_53").removeClass("hide_me_form_ar");
				jQuery("#nm_letter_creation_form_53")
					.find(".frm_to_user_input input[type='email']")
					.val(currentUser);
				jQuery("#nm_letter_creation_form_53")
					.find(".nm_to_email_input input[type='email']")
					.val(toUser);
			}
		},
	);
	// set the emails
	// jQuery(".nm_email_letter_btns").on("click", function (e) {});
	// generate letter

	jQuery("#nm_entry_details_wrapper").on(
		"click",
		"#nm_generate_letter",
		function (e) {
			e.preventDefault();

			// Clear previous errors
			jQuery(".nm-field-error").remove();

			let hasError = false;

			const letter_date = jQuery("#nm_letter_date").val();
			const entry_id = jQuery(this).attr("entry-id");
			const toggle_entry_status = jQuery(
				"input[name='toggle_entry_status']:checked",
			).val();
			const nm_program_name = jQuery("#nm_program_name").val();
			const nm_letter_message = jQuery("#nm_letter_message").val();
			const nm_letter_durations = jQuery("#nm_letter_durations").val();
			const nm_program_deny_reason = jQuery("#nm_program_deny_reason").val();
			const agency_id = getUrlParameter("agency-id");
			const letter_for = jQuery("#nm_program_name_iop").length
				? jQuery("#nm_program_name_iop").val()
				: "";

			/**
			 * Helper to show inline field error
			 */
			function showFieldError(selector, message) {
				jQuery(selector).after(
					`<div class="nm-field-error" style="color:red;font-size:12px;">${message}</div>`,
				);
				hasError = true;
			}

			/**
			 * ===============================
			 * VALIDATION LOGIC
			 * ===============================
			 */

			if (toggle_entry_status === "approve") {
				if (!letter_date)
					showFieldError("#nm_letter_date", "This field is required.");
				if (!nm_program_name)
					showFieldError("#nm_program_name", "This field is required.");
				if (!nm_letter_durations)
					showFieldError("#nm_letter_durations", "This field is required.");
				// nm_program_deny_reason intentionally skipped
			}

			if (toggle_entry_status === "deny") {
				if (!nm_program_deny_reason) {
					showFieldError("#nm_program_deny_reason", "Deny reason is required.");
				}

				if (!entry_id) {
					alert("Entry ID is missing. Cannot proceed.");
					hasError = true;
				}

				if (!agency_id) {
					alert("Agency ID is missing. Cannot proceed.");
					hasError = true;
				}
			}

			// Stop execution if validation failed
			if (hasError) {
				return;
			}

			/**
			 * ===============================
			 * AJAX REQUEST
			 * ===============================
			 */
			jQuery.ajax({
				url: nm_ajax_obj.ajaxurl,
				type: "POST",
				data: {
					action: "nm_generate_letter_callback",
					security: nm_ajax_obj.nonce,
					letter_date: letter_date,
					toggle_entry_status: toggle_entry_status,
					nm_program_name: nm_program_name,
					nm_letter_message: nm_letter_message,
					nm_letter_durations: nm_letter_durations,
					entry_id: entry_id,
					agency_id: agency_id,
					letter_for: letter_for,
					deny_reason: nm_program_deny_reason,
				},
				beforeSend() {
					jQuery("#nm_generate_letter").prop("disabled", true);
				},
				success(res) {
					if (jQuery("#letter_previews_nm").length) {
						if (res && res.data) {
							jQuery("#letter_previews_nm").show();
							jQuery("#letter_preview_wrapper").html(res.data.letter_mockup);
							jQuery("#nm_save_letter").attr(
								"entry-status",
								toggle_entry_status,
							);
							jQuery("#nm_letter_actions").hide();
							jQuery("#nm_download_pdf").attr("href", res.data.url);
							jQuery("#nm_action_after_letter").show();
						}
					} else {
						window.location.reload();
					}
				},
				complete() {
					jQuery("#nm_generate_letter").prop("disabled", false);
				},
			});
		},
	);

	jQuery("#send_email_nm").on("click", function (e) {
		e.preventDefault();
		console.log("Es");
		jQuery("#nm_email_modal_wrapper").show();
		var current_email = jQuery("#nm_email_modal_wrapper").attr("agency-email");
		jQuery("#nm_email_modal_wrapper")
			.find(".nm_to_email_input input[type='email']")
			.val(current_email);
	});
	jQuery("#nm_entry_details_wrapper").on(
		"click",
		"#ask_provider_for_new_documents",
		function (e) {
			e.preventDefault();
			jQuery("#send_email_nm").click();
		},
	);
	jQuery("#close_send_email_nm").on("click", function (e) {
		e.preventDefault();
		jQuery("#nm_email_modal_wrapper").hide();
	});

	jQuery("#update_address_nm").on("click", function (e) {
		e.preventDefault();
		jQuery("#nm_modal_wrapper").addClass("show_modal_nm");
		// VERY IMPORTANT
		setTimeout(function () {
			if (typeof FrmForm !== "undefined") {
				FrmForm.init();
			}
		}, 100);
	});
	jQuery("#close_btns_modal").on("click", function (e) {
		e.preventDefault();
		jQuery("#nm_modal_wrapper").removeClass("show_modal_nm");
	});

	// update address form update
	jQuery("#nm_modal_wrapper").on("submit", "form", function (e) {
		e.preventDefault();
		e.stopImmediatePropagation(); // 🔥 THIS IS THE KEY

		const $form = jQuery(this);

		// Stop if Formidable validation failed
		if ($form.find(".frm_error").length) {
			return false;
		}

		var fieldid = $form
			.find(".frm_combo_inputs_container > div > input")
			.attr("data-geofieldid");

		jQuery.ajax({
			url: nm_ajax_obj.ajaxurl,
			type: "POST",
			data: {
				action: "nm_update_agency_details",
				security: nm_ajax_obj.nonce,
				post_id: jQuery("#nm_modal_wrapper").attr("agency-id"),
				user_id: jQuery("#nm_modal_wrapper").attr("user-id"),

				address_1: $form
					.find(`input[name='item_meta[${fieldid}][line1]']`)
					.val(),
				address_2: $form
					.find(`input[name='item_meta[${fieldid}][line2]']`)
					.val(),
				city: $form.find(`input[name='item_meta[${fieldid}][city]']`).val(),
				state: $form.find(`select[name='item_meta[${fieldid}][state]']`).val(),
				zip: $form.find(`input[name='item_meta[${fieldid}][zip]']`).val(),
				country: $form
					.find(`input[name='item_meta[${fieldid}][country]']`)
					.val(),
			},
			beforeSend() {
				$form.find("button[type='submit']").prop("disabled", true);
			},
			success(res) {
				if (res.success) {
					jQuery("#nm_modal_wrapper .nm-modal-body").html(
						"<p>Address Updated Successfully</p>",
					);
					setTimeout(function () {
						location.reload();
					}, 3000);
				} else {
					alert(res.data.message || "Something went wrong");
				}
			},
			complete() {
				$form.find("button[type='submit']").prop("disabled", false);
			},
		});

		return false; // extra safety
	});

	$("#lookup_emails_nm").on("click", function (e) {
		e.preventDefault();

		var email = $("input#field_i5s1e").val().trim();
		var agencyName = $("input#field_8ptmz").val().trim();

		// Require at least one field to be filled
		if (!email && !agencyName) {
			alert("Please enter an Email or Agency Name to search.");
			return;
		}

		// Helper: render the agency details into the container
		function renderAgencyResults(response) {
			if (response.success) {
				if (response.data.exists) {
					let agency_details = "";
					if (response.data.agency_details && response.data.agency_details.length > 0) {
						response.data.agency_details.forEach((agency) => {
							agency_details += appendUserData(agency);
						});
					} else {
						agency_details = "<p class='not_found_ar'>No Agency Associated with the provided details.</p>";
					}
					$("#lookup_details_nm").html(agency_details);
				} else {
					$("#lookup_details_nm").html("<p class='not_found_ar'>No matching agency found.</p>");
				}
			} else {
				alert("Error: " + response.data);
			}
		}

		// Email takes priority if filled; fallback to agency name
		if (email) {
			$.ajax({
				url: nm_ajax_obj.ajaxurl,
				type: "POST",
				dataType: "json",
				data: {
					action: "register_email_lookup",
					security: nm_ajax_obj.nonce,
					email: email,
				},
				success: renderAgencyResults,
				error: function (xhr, status, error) {
					console.error("AJAX error:", error);
				},
			});
		} else {
			// Only agency name is filled
			$.ajax({
				url: nm_ajax_obj.ajaxurl,
				type: "POST",
				dataType: "json",
				data: {
					action: "register_agency_lookup_by_name",
					security: nm_ajax_obj.nonce,
					agency_name: agencyName,
				},
				success: renderAgencyResults,
				error: function (xhr, status, error) {
					console.error("AJAX error:", error);
				},
			});
		}
	});
	$("#dateRangeToggle").on("click", function (e) {
		e.preventDefault();

		const $dateRange = $("#date_range");

		const input = document.getElementById("date_range");

		if (input && input._flatpickr) {
			input._flatpickr.open();
		}
	});
	// update field value with the default value
	$("input[data-frmval], textarea[data-frmval], select[data-frmval]").each(
		function () {
			console.log($(this).attr("data-frmval"));
			$(this).attr("value", $(this).attr("data-frmval"));
			console.log($(this).val());
		},
	);

	// nm-dashboard-tabs event listener
	$("#nm-dashboard-tabs").on("click", ".nmtab", function (e) {
		e.preventDefault();
		$("#nm-dashboard-tabs .nmtab").removeClass("active");
		$(this).addClass("active");
		filtersDashboardNM();
	});
	jQuery("#sortSelect").on("change", function (e) {
		e.preventDefault();
		filtersDashboardNM();
	});
	jQuery("#statusSelect").on("change", function (e) {
		e.preventDefault();
		filtersDashboardNM();
	});
	jQuery("#pagination_nm").on("click", "li", function (e) {
		e.preventDefault();
		jQuery("#pagination_nm li").removeClass("active");
		$(this).addClass("active");
		filtersDashboardNM();
	});
	// flatpickr("#date_range", {
	// 	mode: "range",
	// 	dateFormat: "Y-m-d",
	// 	onChange: function (selectedDates) {
	// 		filtersDashboardNM();
	// 	},
	// });
	let flatpickrInstanceNM = flatpickr("#date_range", {
		mode: "range",
		dateFormat: "Y-m-d",
		onChange: function (selectedDates) {
			if (selectedDates.length === 2) {
				filtersDashboardNM();
			}
		},
	});

	jQuery("#nmresetFilters").on("click", function (e) {
		e.preventDefault();
		jQuery("#sortSelect").val("DESC");
		jQuery("#statusSelect").val("");
		// Reset date range (flatpickr)
		if (flatpickrInstanceNM) {
			flatpickrInstanceNM.clear();
		}
		filtersDashboardNM();
	});

	jQuery("#nm_entry_details_wrapper").on(
		"click",
		".nmtabdetailed_entry",
		function (e) {
			e.preventDefault();
			jQuery(".nm-document-tabs .tab").removeClass("active");
			var currenttabid = $(this).attr("tab-id");
			jQuery("#nm-application-details .tab-content").hide();
			jQuery(`#${currenttabid}`).show();
			if (currenttabid == "tab-signing") {
				$entryid = jQuery(`#${currenttabid}`).attr("entry-id");
				if (jQuery("#document_doc_esign_doc").attr("iframeloaded") == "false") {
					jQuery.ajax({
						url: nm_ajax_obj.ajaxurl,
						type: "POST",
						data: {
							action: "nm_load_doc_callback",
							security: nm_ajax_obj.nonce,
							entry_id: $entryid,
						},
						beforeSend() {
							jQuery(`#${currenttabid}`)
								.find(".loader_nm")
								.removeClass("hide_this_loader_nm");
						},
						success(response) {
							jQuery("#document_doc_esign_doc").html(response.data.esign_doc);
							jQuery("#document_doc_esign_doc").attr("iframeloaded", "true");
						},
						error(xhr, status, error) {
							console.error("AJAX error:", error);
						},
						complete() {
							jQuery(`#${currenttabid}`)
								.find(".loader_nm")
								.addClass("hide_this_loader_nm");
						},
					});
				}
			}
		},
	);

	jQuery("#nmtableBody").on("click", ".entry_id_filter", function (e) {
		e.preventDefault();
		var entry_id = $(this).attr("entry-id");
		var agency_id = $(this).closest("tr").attr("agency-id");
		sessionStorage.setItem("agency_detail_current_app", entry_id);

		sessionStorage.setItem(
			"agency_details_current_agency",
			getUrlParameter("agency-id"),
		);
		filtersEntriesNM(entry_id, agency_id);
	});

	jQuery("#nm_entry_details_wrapper").on(
		"click",
		".nmattachment_status_update",
		function (e) {
			e.preventDefault();
			jQuery("#nm_confirmation_modal").show();
			var status = $(this).attr("entry-status");
			var attachid = $(this).attr("attachment-id");
			jQuery("#proceed_confirmation_nm").attr("entry-status", status);
			jQuery("#proceed_confirmation_nm").attr("attachment-id", attachid);
			// updateEntryStatus(status, attachid);
		},
	);

	jQuery(".close_confirmation_nm").on("click", function (e) {
		e.preventDefault();
		jQuery("#nm_confirmation_modal").hide();
		// updateEntryStatus(status, attachid);
	});

	jQuery("#proceed_confirmation_nm").on("click", function (e) {
		e.preventDefault();

		var status = $(this).attr("entry-status");
		var attachid = $(this).attr("attachment-id");
		updateEntryStatus(status, attachid);
	});

	jQuery("#nm_entry_details_wrapper").on(
		"click",
		".replace_nm_docs",
		function (e) {
			e.preventDefault();
			var entryid = $(this).attr("entry-id");
			var fieldid = $(this).attr("field-id");
			var fieldname = $(this).attr("field-name");

			jQuery("#replace_request_formw_ar").attr("entry-id", entryid);
			jQuery(".replace_doc_app_heading span").text(entryid);
			jQuery(".replace_doc_field_name span").text(fieldname);
			jQuery("#resume-upload-nm").attr("field-id", `${fieldid}`);
			jQuery("#replace_request_formwrapper_ar").show();
		},
	);

	jQuery("#nm_entry_details_wrapper").on(
		"change",
		"input[name='toggle_supplemental_docs']",
		function (e) {
			e.preventDefault();
			var value = $(this).val();
			if (value == "yes") {
				jQuery("#nm_supplemental_docs_form_wraper").show();
				jQuery("#nm_supplement_footer_actions").show();
			} else {
				jQuery("#nm_supplemental_docs_form_wraper").hide();
				jQuery("#nm_supplement_footer_actions").hide();
			}
		},
	);

	// replacing documents form submit
	jQuery("#nm_entry_details_wrapper").on(
		"submit",
		"#replace_request_formw_ar",
		function (e) {
			e.preventDefault();
			var $form = jQuery("#replace_request_formw_ar");

			// Clear previous error messages
			$form.find(".nm_error_message").remove();
			$form.find(".nm_error").removeClass("nm_error");

			// Validation
			let isValid = true;

			// Validate main file upload
			const mainFile = $("#resume-upload-nm")[0].files[0];
			if (!mainFile) {
				isValid = false;
				showError(
					$("#resume-upload-nm").closest(".upload-box"),
					"Please upload a file",
				);
			}

			// Validate main note field
			const mainNote = $("textarea[name='application_note']").val().trim();
			if (!mainNote) {
				isValid = false;
				showError(
					$("textarea[name='application_note']"),
					"Please add a note for the main upload",
				);
			}

			// If validation fails, stop submission
			if (!isValid) {
				return false;
			}

			const formData = new FormData(this);

			/**
			 * Add additional dynamic values
			 * based on document name
			 */
			const documentName = $("#doc-name").val();
			if (documentName) {
				formData.append(
					"document_slug",
					documentName.toLowerCase().replace(/\s+/g, "_"),
				);
				formData.append("document_label", `Supplemental - ${documentName}`);
			}

			var entryid = jQuery("#replace_request_formw_ar").attr("entry-id");
			var fieldid = jQuery("#resume-upload-nm").attr("field-id");
			// Example: add current post or application ID
			formData.append("entry_id", entryid);
			formData.append("field_id", fieldid);
			formData.append("action", "nm_replace_application_upload");

			$.ajax({
				url: nm_ajax_obj.ajaxurl, // WordPress AJAX URL
				type: "POST",
				data: formData,
				processData: false,
				contentType: false,
				beforeSend() {
					$form.find(".btn-submit").prop("disabled", true);
				},
				success(response) {
					if (response.success) {
						alert("Form submitted successfully");
						location.reload();
					} else {
						alert(response.data?.message || "Submission failed");
					}
				},
				error() {
					alert("AJAX error occurred");
				},
				complete() {
					$form.find(".btn-submit").prop("disabled", false);
				},
			});
		},
	);

	jQuery("#nm_entry_details_wrapper").on(
		"submit",
		"#replace_supplement_request_formw_ar",
		function (e) {
			e.preventDefault();
			var $form = jQuery("#replace_supplement_request_formw_ar");

			// Clear previous error messages
			$form.find(".nm_error_message").remove();
			$form.find(".nm_error").removeClass("nm_error");

			// Validation
			let isValid = true;

			// Check if supplemental documents radio is "yes"
			const supplementalDocsValue = $(
				"input[name='toggle_supplemental_docs']:checked",
			).val();

			if (supplementalDocsValue === "yes") {
				// Validate document name
				const docName = $("#doc-name").val().trim();
				if (!docName) {
					isValid = false;
					showError($("#doc-name"), "Please enter a document name");
				}

				// Validate supporting document upload
				const supportFile = $("#support-doc-upload")[0].files[0];
				if (!supportFile) {
					isValid = false;
					showError(
						$("#support-doc-upload").closest(".upload-box"),
						"Please upload a supporting document",
					);
				}

				// Validate supporting document note
				const supportNote = $("textarea[name='supporting_note']").val().trim();
				if (!supportNote) {
					isValid = false;
					showError(
						$("textarea[name='supporting_note']"),
						"Please add a note for the supporting document",
					);
				}
			}

			// If validation fails, stop submission
			if (!isValid) {
				return false;
			}

			const formData = new FormData(this);

			/**
			 * Add additional dynamic values
			 * based on document name
			 */
			const documentName = $("#doc-name").val();
			if (documentName) {
				formData.append(
					"document_slug",
					documentName.toLowerCase().replace(/\s+/g, "_"),
				);
				formData.append("document_label", `Supplemental - ${documentName}`);
			}

			var entryid = jQuery("#replace_supplement_request_formw_ar").attr(
				"entry-id",
			);
			// Example: add current post or application ID
			formData.append("entry_id", entryid);
			formData.append("action", "nm_replace_application_upload");

			$.ajax({
				url: nm_ajax_obj.ajaxurl, // WordPress AJAX URL
				type: "POST",
				data: formData,
				processData: false,
				contentType: false,
				beforeSend() {
					$form.find(".btn-submit").prop("disabled", true);
				},
				success(response) {
					if (response.success) {
						alert("Form submitted successfully");
						location.reload();
					} else {
						alert(response.data?.message || "Submission failed");
					}
				},
				error() {
					alert("AJAX error occurred");
				},
				complete() {
					$form.find(".btn-submit").prop("disabled", false);
				},
			});
		},
	);

	// Helper function to show error messages
	function showError($element, message) {
		$element.addClass("nm_error");
		const $errorMsg = $(
			'<span class="nm_error_message" style="color: #DB3239; font-size: 14px; display: block; margin-top: 5px;">' +
			message +
			"</span>",
		);
		$element.after($errorMsg);
	}

	jQuery("#nm_entry_details_wrapper").on(
		"change",
		"#resume-upload-nm",
		function (e) {
			if (!this.files || !this.files.length) return;

			const file = this.files[0];
			const fileType = file.type;

			const $previewBox = jQuery("#replace_doc_preview");
			const $thumbBox = jQuery("#replace_doc_thumb");
			const $fileName = jQuery("#replace_doc_file_name");

			// Reset preview
			$thumbBox.empty();
			$fileName.text(file.name);

			// IMAGE PREVIEW
			if (fileType.startsWith("image/")) {
				const reader = new FileReader();
				reader.onload = function (ev) {
					$thumbBox.html(`<img src="${ev.target.result}" alt="Preview">`);
				};
				reader.readAsDataURL(file);

				// PDF PREVIEW
			} else if (fileType === "application/pdf") {
				$thumbBox.html(`<i class="fa-solid fa-file-pdf pdf-icon"></i>`);

				// OTHER FILE TYPES
			} else {
				$thumbBox.html(`<i class="fa-solid fa-file file-icon"></i>`);
			}

			// Show preview + update UI
			$previewBox.show();
			jQuery("#content_wrappers_nm_replace").hide();
			jQuery(".nm_file_upload_drop").addClass("has-file");
		},
	);

	jQuery("#nm_entry_details_wrapper").on(
		"change",
		"#support-doc-upload",
		function (e) {
			if (!this.files || !this.files.length) return;

			const file = this.files[0];
			const fileType = file.type;

			const $previewBox = jQuery("#supplement_doc_preview");
			const $thumbBox = jQuery("#supplement_doc_thumb");
			const $fileName = jQuery("#supplement_doc_file_name");

			// Reset preview
			$thumbBox.empty();
			jQuery("#nm_entry_details_wrapper").on(
				"change",
				"#support-doc-upload",
				function (e) { },
			);
			$fileName.text(file.name);

			// IMAGE PREVIEW
			if (fileType.startsWith("image/")) {
				const reader = new FileReader();
				reader.onload = function (ev) {
					$thumbBox.html(`<img src="${ev.target.result}" alt="Preview">`);
				};
				reader.readAsDataURL(file);

				// PDF PREVIEW
			} else if (fileType === "application/pdf") {
				$thumbBox.html(`<i class="fa-solid fa-file-pdf pdf-icon"></i>`);

				// OTHER FILE TYPES
			} else {
				$thumbBox.html(`<i class="fa-solid fa-file file-icon"></i>`);
			}

			// Show preview + update UI
			$previewBox.show();
			jQuery("#content_wrappers_nm_supplement").hide();
			jQuery(".nm_file_upload_drop").addClass("has-file");
		},
	);
	jQuery("#nm_entry_details_wrapper").on(
		"click",
		".nm-remove-file",
		function (e) {
			e.preventDefault();
			jQuery(this)
				.closest(".nm-file-preview")
				.siblings("input[type='file']")
				.val("");
			jQuery(this)
				.closest(".nm-file-preview")
				.siblings(".content_wrappers_nm")
				.show();
			jQuery(this).closest(".nm-file-preview").hide();
		},
	);

	jQuery("#nm_exp_to_csv").on("click", function (e) {
		e.preventDefault();
		filtersDashboardNM(true);
	});
});

/**
 * Append user details to a container on the frontend
 *
 * @param {Object} user - User data object returned from AJAX
 * @param {string} containerSelector - CSS selector of the container where data will be appended
 */
/**
 * Append user/agency details to a container on the frontend
 *
 * @param {Object} agency - Agency data object returned from AJAX
 * @returns {string} - HTML markup for the agency details
 */
function appendUserData(agency) {
	if (!agency) return "";

	// Build sub-location HTML if repeater exists
	let subLocationsHTML = "";
	if (agency.sub_locations && agency.sub_locations.length > 0) {
		subLocationsHTML = `
      <div class="nm-sub-locations">
        <h5>Sub Locations:</h5>
        <ul>
          ${agency.sub_locations
				.map(
					(loc, i) => `
              <li class="nm-sub-location-item">
                <strong>Location ${i + 1}:</strong><br>
                <span><strong>Physical Address:</strong> ${loc.physical_address || "N/A"
						}</span><br>
                <span><strong>Same Mailing Address:</strong> ${loc.is_same || "N/A"
						}</span><br>
                <span><strong>Mailing Address:</strong> ${loc.mailing_address || "N/A"
						}</span>
              </li>
            `,
				)
				.join("")}
        </ul>
      </div>
    `;
	}

	// Main wrapper
	const userDiv = `
    <div class="nm-user-data card">
      <div class="card-header">
        <h3><strong>Agency Name:</strong> ${agency.title || "Agency Details"
		}</h3>
        <p><strong>Phone:</strong> ${agency.phone || "—"}</p>
        <p><strong>Email:</strong> ${agency.email || "—"}</p>
        <p><strong>Agency Type:</strong> ${agency.agency_type || "—"}</p>
               <a href="${agency.url || "#"
		}" target="_blank" class="nm-agency-link">View Agency</a>
      </div>
		<div class="nm_main_location">
			<p><strong>Physical Address:</strong> ${agency.physical_address || "—"}</p>
			<p><strong>Same Mailing Address:</strong> ${agency.same_address || "—"}</p>
			<p><strong>Mailing Address:</strong> ${agency.mailing_address || "—"}</p>
	    </div>
       <div class="card-body">
  
        ${subLocationsHTML}
      </div>
    </div>
  `;

	return userDiv;
}

function restoreFlatpickrRange(state) {
	if (!state?.daterange?.startDate || !state?.daterange?.endDate) return;

	const input = document.querySelector("#date_range"); // 🔴 CHANGE if needed
	if (!input || !input._flatpickr) return;

	input._flatpickr.setDate(
		[state.daterange.startDate, state.daterange.endDate],
		true, // trigger change
	);
}

/**
 * Filters on dashboard
 *
 */
function filtersDashboardNM($exportcsv = false, restoredState = null) {
	var state = restoredState || {};

	var search = jQuery("#nm_search_input").val();

	var $sort = state.sort ?? jQuery("#sortSelect").val();
	var $currentForm =
		state.currentForm ??
		jQuery("#nm-dashboard-tabs .nmtab.active").attr("form-id");
	var $status = state.status ?? jQuery("#statusSelect").val();
	var $daterange = state.daterange ?? getDateRange();
	var $currentPage =
		state.currentPage ??
		jQuery("#pagination_nm li.active").attr("page-id") ??
		1;
	var entryId = state.agency_id ?? getUrlParameter("agency-id");
	var $currentpagetype =
		state.currentpagetype ?? jQuery("#pagination_nm").attr("data-type");

	// 🔑 SAVE STATE before request
	saveNMDashboardState({
		currentPage: $currentPage,
	});

	jQuery.ajax({
		url: nm_ajax_obj.ajaxurl,
		type: "POST",
		dataType: "json",
		data: {
			action: "nm_filter_dashboard",
			security: nm_ajax_obj.nonce,
			currentForm: $currentForm ?? -1,
			sortby: $sort,
			status: $status,
			datefrom: $daterange.startDate,
			dateto: $daterange.endDate,
			currentpage: $currentPage,
			agency_id: entryId,
			currentpagetype: $currentpagetype,
			exportcsv: $exportcsv,
			search: search,
		},
		beforeSend() {
			jQuery("#nm_table_container_wrapper")
				.find(".loader_nm")
				.removeClass("hide_this_loader_nm");
		},
		success: function (response) {
			jQuery("#nmtableBody").html(response.data.entrylistmockup);
			jQuery("#pagination_nm").html(response.data.paginationlistmockup);

			if (response?.data?.csv_file_export?.success == false) {
				jQuery("#nm_exp_to_csv").addClass("nm_disabled_btn");
			} else {
				jQuery("#nm_exp_to_csv").removeClass("nm_disabled_btn");
			}

			if ($exportcsv && response.data.csv_file_export) {
				const link = document.createElement("a");
				link.href = response.data.csv_file_export.file_url;
				link.download = response.data.csv_file_export.filename || "export.csv";
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);
			}
		},
		complete() {
			jQuery("#nm_table_container_wrapper")
				.find(".loader_nm")
				.addClass("hide_this_loader_nm");
		},
		error: function (xhr, status, error) {
			console.error("AJAX error:", error);
		},
	});
}

function restoreNMDashboardState() {
	const saved = sessionStorage.getItem(NM_DASHBOARD_STATE_KEY);
	if (!saved) return false;

	const state = JSON.parse(saved);

	// Restore selects
	if (state.sort) jQuery("#sortSelect").val(state.sort);
	if (state.status) jQuery("#statusSelect").val(state.status);

	// Restore active tab
	if (state.currentForm) {
		jQuery("#nm-dashboard-tabs .nmtab").removeClass("active");
		jQuery(
			`#nm-dashboard-tabs .nmtab[form-id="${state.currentForm}"]`,
		).addClass("active");
	}

	// ✅ Restore flatpickr UI
	restoreFlatpickrRange(state);

	// Trigger AJAX with restored state
	filtersDashboardNM(false, state);

	return true;
}

function saveNMDashboardState(extra = {}) {
	const state = {
		sort: jQuery("#sortSelect").val(),
		currentForm: jQuery("#nm-dashboard-tabs .nmtab.active").attr("form-id"),
		status: jQuery("#statusSelect").val(),
		daterange: getDateRange(),
		currentPage:
			extra.currentPage ??
			jQuery("#pagination_nm li.active").attr("page-id") ??
			1,
		currentpagetype: jQuery("#pagination_nm").attr("data-type"),
		// agency_id: getUrlParameter("agency-id"),
	};

	sessionStorage.setItem(NM_DASHBOARD_STATE_KEY, JSON.stringify(state));
}

/**
 * Filters on dashboard detailed
 *
 */
function filtersEntriesNM(entry_id, agency_id = null) {
	// var $sort= jQuery("#sortSelect").val();
	jQuery("#nm_entry_details_wrapper")
		.find(".loader_nm")
		.removeClass("hide_this_loader_nm");

	if (!agency_id) {
		agency_id = getUrlParameter("agency-id");
	}

	jQuery.ajax({
		url: nm_ajax_obj.ajaxurl,
		type: "POST",
		dataType: "json",
		data: {
			action: "nm_filter_entries",
			security: nm_ajax_obj.nonce,
			entry_id: entry_id,
			agency_id: agency_id,
		},
		success: function (response) {
			jQuery("#nm_entry_details_wrapper").html(
				response.data.entrydetailsmockup,
			);
			jQuery("#nm_detailed_logs").html(response.data.note_activitymockup);
			if (response.data.sidebar_contact_card) {
				jQuery("#nm_agency_contact_card").html(response.data.sidebar_contact_card);
			}
			jQuery("#nm_entry_details_wrapper")
				.find(".loader_nm")
				.addClass("hide_this_loader_nm");
		},
		error: function (xhr, status, error) {
			console.error("AJAX error:", error);
		},
	});
}

/**
 * Filters on dashboard detailed
 *
 */
function updateEntryStatus(status, attachid) {
	// var $sort= jQuery("#sortSelect").val();

	jQuery.ajax({
		url: nm_ajax_obj.ajaxurl,
		type: "POST",
		dataType: "json",
		data: {
			action: "nm_attachment_status",
			security: nm_ajax_obj.nonce,
			status: status,
			attachid: attachid,
		},
		success: function (response) {
			jQuery(
				`.nmattachment_status_update[attachment-id='${attachid}']`,
			).addClass("nm_disabled");
			console.log(
				jQuery(
					`.nmattachment_status_update[attachment-id='${attachid}'][entry-status='${status}']`,
				),
			);

			jQuery(
				`.nmattachment_status_update[attachment-id='${attachid}'][entry-status='${status}']`,
			).addClass(`nmstatus_${status}`);
			jQuery(
				`.nmattachment_status_update[attachment-id='${attachid}'][entry-status='${status}']`,
			).text(status == "0" ? "REJECTED" : "ACCEPTED");
			jQuery("#nm_confirmation_modal").hide();
			var storedState = sessionStorage.getItem("nm_dashboard_state");

			if (storedState) {
				var currentPageTypeRestored = JSON.parse(storedState);

				console.log(currentPageTypeRestored);

				if (currentPageTypeRestored.currentpagetype === "agency_details") {
					setTimeout(function () {
						const url = new URL(window.location.href);
						url.searchParams.set("restore", "true");
						window.location.href = url.toString();
					}, 100);
				} else {
					setTimeout(function () {
						window.location.reload();
					}, 100);
				}
			} else {
				setTimeout(function () {
					window.location.reload();
				}, 100);
			}
		},
		error: function (xhr, status, error) {
			console.error("AJAX error:", error);
		},
	});
}
function getUrlParameter(name) {
	name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
	var searchParams = new URLSearchParams(window.location.search);
	return searchParams.get(name) ?? "";
}

function getDateRange() {
	if (!document.getElementById("date_range")) {
		return {
			startDate: "",
			endDate: "",
		};
	}
	const value = document.getElementById("date_range").value;

	if (!value.includes(" to "))
		return {
			startDate: "",
			endDate: "",
		};

	const [start, end] = value.split(" to ");

	return {
		startDate: start.trim(),
		endDate: end.trim(),
	};
}

// Example usage ANYWHERE:
const range = getDateRange();
console.log(range.startDate, range.endDate);

/* =========================================
   NM AGENCY NOTES LOG MODULE
========================================= */
(function ($) {
	var pendingDeleteId = 0;

	function noteAgencyId() {
		var fromInput = $('#nm_note_agency_id').val();
		if (fromInput) return fromInput;
		var fromList = $('#nm_notes_list').data('agency');
		if (fromList) return fromList;
		return getUrlParameter('agency-id') || '';
	}

	function openFormModal(noteId, title, text) {
		$('#nm_note_id').val(noteId || '');
		$('#nm_note_title_input').val(title || '');
		$('#nm_note_text_input').val(text || '');
		$('#nm_note_modal_title').text(noteId ? 'Edit Note' : 'Add Note');
		$('#nm_note_form_modal').show();
	}

	function closeAll() {
		$('#nm_note_form_modal').hide();
		$('#nm_note_view_modal').hide();
		$('#nm_note_delete_modal').hide();
		$('#nm_all_notes_modal').hide();
	}

	// Close any modal via custom close button
	$(document).on('click', '#close_send_email_nm', function () {
		closeAll();
	});

	// Add note
	$(document).on('click', '#nm_add_note_btn', function () {
		openFormModal('', '', '');
	});

	// Edit note
	$(document).on('click', '.nm-note-edit', function () {
		openFormModal($(this).data('id'), $(this).data('title'), $(this).data('text'));
	});

	// View note
	$(document).on('click', '.nm-note-view', function () {
		$('#nm_view_note_title').text($(this).data('title'));
		$('#nm_view_note_text').text($(this).data('text'));
		$('#nm_view_note_user').text($(this).data('user'));
		$('#nm_view_note_date').text($(this).data('date'));
		$('#nm_note_view_modal').show();
	});

	// Delete note — show confirm
	$(document).on('click', '.nm-note-delete', function () {
		pendingDeleteId = $(this).data('id');
		$('#nm_note_delete_modal').show();
	});

	// Delete confirm — reload after success
	$(document).on('click', '#nm_note_delete_confirm', function () {
		if (!pendingDeleteId) return;
		var $btn = $(this);
		$.ajax({
			url: nm_ajax_obj.ajaxurl,
			type: 'POST',
			data: { action: 'nm_notes_delete', security: nm_ajax_obj.nonce, note_id: pendingDeleteId },
			beforeSend: function () {
				$btn.prop('disabled', true);
			},
			success: function (res) {
				if (res.success) {
					closeAll();
					pendingDeleteId = 0;
					window.location.reload();
				} else {
					alert(res.data.message || 'Error deleting note.');
				}
			},
			complete: function () {
				$btn.prop('disabled', false);
			},
			error: function () {
				alert('Request failed. Please try again.');
			}
		});
	});

	// Save note (add/edit) — reload after success
	$(document).on('click', '#nm_note_form_save', function () {
		var title = $('#nm_note_title_input').val().trim();
		var text = $('#nm_note_text_input').val().trim();
		if (!title || !text) { alert('Title and note text are required.'); return; }
		var $btn = $(this);
		$.ajax({
			url: nm_ajax_obj.ajaxurl,
			type: 'POST',
			data: {
				action: 'nm_notes_save',
				security: nm_ajax_obj.nonce,
				agency_id: noteAgencyId(),
				note_id: $('#nm_note_id').val(),
				note_title: title,
				note_text: text
			},
			beforeSend: function () {
				$btn.prop('disabled', true);
			},
			success: function (res) {
				if (res.success) {
					closeAll();
					window.location.reload();
				} else {
					alert(res.data.message || 'Error saving note.');
				}
			},
			complete: function () {
				$btn.prop('disabled', false);
			},
			error: function () {
				alert('Request failed. Please try again.');
			}
		});
	});

	// Close buttons
	$(document).on('click',
		'#nm_note_modal_close, #nm_note_form_cancel, #nm_view_note_modal_close, .nm-note-delete-cancel, #nm_all_notes_close',
		closeAll
	);

	// Close on overlay click
	$(document).on('click', '.nm-note-modal-wrapper .nm-modal-overlay', function (e) {
		if ($(e.target).hasClass('nm-modal-overlay')) closeAll();
	});

	// See all notes button — open modal immediately with loader visible
	$(document).on('click', '#nm_see_all_notes_btn', function () {
		// Show modal right away so the loader gif is visible during AJAX
		$('#nm_all_notes_list').children(':not(.loader_nm)').hide();
		$('#nm_all_notes_list').find('.loader_nm').removeClass('hide_this_loader_nm');
		$('#nm_notes_pagination').html('');
		$('#nm_all_notes_modal').show();
		loadAllNotes(1);
	});

	// Load all notes with pagination
	function loadAllNotes(page) {
		var agencyId = noteAgencyId();
		$('#nm_back_to_all_notes').hide();
		$('#nm_notes_pagination').show();

		$.ajax({
			url: nm_ajax_obj.ajaxurl,
			type: 'POST',
			data: {
				action: 'nm_notes_get_all',
				security: nm_ajax_obj.nonce,
				agency_id: agencyId,
				page: page,
				per_page: 5
			},
			beforeSend: function () {
				// Same small loader gif for both initial open and pagination
				$('#nm_all_notes_list').find('.loader_nm').removeClass('hide_this_loader_nm');
				$('#nm_all_notes_list').children(':not(.loader_nm)').hide();
			},
			success: function (res) {
				if (res.success) {
					renderAllNotes(res.data.notes, res.data.current_user_id, res.data.can_write);
					renderPagination(res.data.total, res.data.page, res.data.per_page);
					if (res.data.agency_name) {
						$('#nm_all_notes_agency').text('(' + res.data.agency_name + ')').show();
					} else {
						$('#nm_all_notes_agency').hide();
					}
				} else {
					alert(res.data.message || 'Error loading notes.');
				}
			},
			complete: function () {
				$('#nm_all_notes_list').find('.loader_nm').addClass('hide_this_loader_nm');
			},
			error: function () {
				alert('Request failed. Please try again.');
			}
		});
	}

	// Render notes in modal
	function renderAllNotes(notes, currentUserId, canWrite) {
		var html = '';
		if (!notes || notes.length === 0) {
			html = '<p class="nm-no-notes">No notes yet.</p>';
		} else {
			notes.forEach(function (note) {
				var isOwn = parseInt(note.added_by) === parseInt(currentUserId);
				var date = new Date(note.created_at).toLocaleString('en-US', {
					month: 'short', day: 'numeric', year: 'numeric',
					hour: 'numeric', minute: '2-digit', hour12: true
				});

				html += '<div class="nm-note-item" data-note-id="' + note.id + '">';
				html += '<div class="content nm-note-content">';
				html += '<div class="nm-note-row-top">';
				html += '<span class="nm-note-title log-text">' + escapeHtml(note.note_title) + '</span>';
				html += '<div class="nm-note-actions">';

				if (isOwn && canWrite) {
					html += '<button class="nm-note-icon-btn nm-note-edit-modal" title="Edit" ';
					html += 'data-id="' + note.id + '" ';
					html += 'data-title="' + escapeHtml(note.note_title) + '" ';
					html += 'data-text="' + escapeHtml(note.note_text) + '">';
					html += '<i class="fa-regular fa-pen-to-square"></i></button>';

					html += '<button class="nm-note-icon-btn nm-note-delete-modal" title="Delete" ';
					html += 'data-id="' + note.id + '">';
					html += '<i class="fa-regular fa-trash-can"></i></button>';
				}

				html += '<button class="nm-note-icon-btn nm-note-view-modal" title="View" ';
				html += 'data-id="' + note.id + '" ';
				html += 'data-title="' + escapeHtml(note.note_title) + '" ';
				html += 'data-text="' + escapeHtml(note.note_text) + '" ';
				html += 'data-user="' + escapeHtml(note.added_by_name) + '" ';
				html += 'data-date="' + date + '">';
				html += '<i class="fa-regular fa-eye"></i></button>';

				html += '</div></div>';
				html += '<p class="log-date-top"><i class="fa-regular fa-clock"></i> ' + date;
				html += '<span class="nm-note-by-inline"><i class="fa-regular fa-user"></i> ';
				html += escapeHtml(note.added_by_name) + '</span></p>';
				html += '</div></div>';
			});
		}
		$('#nm_all_notes_list').html(html + $('#nm_all_notes_list .loader_nm')[0].outerHTML);
	}

	// Render pagination
	function renderPagination(total, currentPage, perPage) {
		var totalPages = Math.ceil(total / perPage);
		$('#nm_notes_pagination').data('current-page', currentPage);

		if (totalPages <= 1) {
			$('#nm_notes_pagination').html('');
			return;
		}

		var html = '<li class="page-item' + (currentPage <= 1 ? ' disabled' : '') + '" data-page="' + (currentPage - 1) + '"><a href="#">&laquo;</a></li>';

		for (var i = 1; i <= totalPages; i++) {
			html += '<li class="page-item' + (i === currentPage ? ' active' : '') + '" data-page="' + i + '"><a href="#">' + i + '</a></li>';
		}

		html += '<li class="page-item' + (currentPage >= totalPages ? ' disabled' : '') + '" data-page="' + (currentPage + 1) + '"><a href="#">&raquo;</a></li>';

		$('#nm_notes_pagination').html(html);
	}

	// Pagination click
	$(document).on('click', '#nm_notes_pagination .page-item', function (e) {
		e.preventDefault();
		if (!$(this).hasClass('disabled')) {
			var page = parseInt($(this).data('page'));
			loadAllNotes(page);
		}
	});

	// Helper function to escape HTML
	function escapeHtml(text) {
		var map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return String(text).replace(/[&<>"']/g, function (m) { return map[m]; });
	}

	// View note from modal - render in modal body
	$(document).on('click', '.nm-note-view-modal', function () {
		var title = $(this).data('title');
		var text = $(this).data('text');
		var user = $(this).data('user');
		var date = $(this).data('date');

		var loaderHtml = $('#nm_all_notes_list .loader_nm')[0].outerHTML;
		$('#nm_notes_pagination').hide();
		$('#nm_back_to_all_notes').show();

		var html = '<div style="padding:20px;">';
		html += '<h3 style="margin-bottom:16px;color:#009bc5;">' + escapeHtml(title) + '</h3>';
		html += '<p style="font-size:13px;line-height:1.6;white-space:pre-wrap;margin-bottom:16px;">' + escapeHtml(text) + '</p>';
		html += '<hr style="margin:16px 0;border:none;border-top:1px solid #e5e7eb;">';
		html += '<p style="font-size:11px;color:#666;">';
		html += '<i class="fa-regular fa-user"></i> ' + escapeHtml(user);
		html += ' &nbsp;&nbsp; <i class="fa-regular fa-clock"></i> ' + escapeHtml(date);
		html += '</p>';
		html += '</div>';

		$('#nm_all_notes_list').html(html + loaderHtml);
	});

	// Back to all notes from view
	$(document).on('click', '#nm_back_to_all_notes', function () {
		var currentPage = $('#nm_notes_pagination').data('current-page') || 1;
		loadAllNotes(currentPage);
	});

	// Edit note from modal - render edit form in modal body
	$(document).on('click', '.nm-note-edit-modal', function () {
		var noteId = $(this).data('id');
		var title = $(this).data('title');
		var text = $(this).data('text');

		var loaderHtml = $('#nm_all_notes_list .loader_nm')[0].outerHTML;
		$('#nm_notes_pagination').hide();

		var html = '<div style="padding:20px;">';
		html += '<input type="hidden" id="nm_edit_note_id_modal" value="' + noteId + '">';
		html += '<div style="margin-bottom:14px;">';
		html += '<label style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;">Title</label>';
		html += '<input type="text" id="nm_edit_note_title_modal" value="' + escapeHtml(title) + '" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:6px;">';
		html += '</div>';
		html += '<div style="margin-bottom:14px;">';
		html += '<label style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;">Note</label>';
		html += '<textarea id="nm_edit_note_text_modal" style="width:100%;height:120px;padding:10px;border:1px solid #e5e7eb;border-radius:6px;resize:vertical;">' + escapeHtml(text) + '</textarea>';
		html += '</div>';
		html += '<div style="display:flex;gap:10px;">';
		html += '<button class="nm-btn nm-btn-secondary" id="nm_cancel_edit_modal">Cancel</button>';
		html += '<button class="nm-btn nm-btn-primary" id="nm_save_edit_modal">Save Changes</button>';
		html += '</div></div>';

		$('#nm_all_notes_list').html(html + loaderHtml);
	});

	// Cancel edit from modal
	$(document).on('click', '#nm_cancel_edit_modal', function () {
		var currentPage = $('#nm_notes_pagination').data('current-page') || 1;
		loadAllNotes(currentPage);
		$('#nm_notes_pagination').show();
	});

	// Save edit from modal
	$(document).on('click', '#nm_save_edit_modal', function () {
		var noteId = $('#nm_edit_note_id_modal').val();
		var title = $('#nm_edit_note_title_modal').val().trim();
		var text = $('#nm_edit_note_text_modal').val().trim();
		var agencyId = noteAgencyId();

		if (!title || !text) { alert('Title and note text are required.'); return; }

		var $btn = $(this);

		$.ajax({
			url: nm_ajax_obj.ajaxurl,
			type: 'POST',
			data: {
				action: 'nm_notes_save',
				security: nm_ajax_obj.nonce,
				agency_id: agencyId,
				note_id: noteId,
				note_title: title,
				note_text: text
			},
			beforeSend: function () {
				$btn.prop('disabled', true);
			},
			success: function (res) {
				if (res.success) {
					var currentPage = $('#nm_notes_pagination').data('current-page') || 1;
					loadAllNotes(currentPage);
					$('#nm_notes_pagination').show();
				} else {
					alert(res.data.message || 'Error saving note.');
				}
			},
			complete: function () {
				$btn.prop('disabled', false);
			},
			error: function () {
				alert('Request failed. Please try again.');
			}
		});
	});

	// Delete note from modal
	$(document).on('click', '.nm-note-delete-modal', function () {
		var noteId = $(this).data('id');

		if (!confirm('Are you sure you want to delete this note?')) return;

		$.ajax({
			url: nm_ajax_obj.ajaxurl,
			type: 'POST',
			data: {
				action: 'nm_notes_delete',
				security: nm_ajax_obj.nonce,
				note_id: noteId
			},
			success: function (res) {
				if (res.success) {
					var currentPage = $('#nm_notes_pagination').data('current-page') || 1;
					loadAllNotes(currentPage);
				} else {
					alert(res.data.message || 'Error deleting note.');
				}
			},
			error: function () {
				alert('Request failed. Please try again.');
			}
		});
	});

}(jQuery));
