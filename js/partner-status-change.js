(function($) {
    'use strict';

    $(document).ready(function() {
        $(document).on('click', '.partner-status-change', function(e) {
            e.preventDefault();
            var post_id = $(this).data('post-id');
            var status = $(this).data('status');
            var $this = $(this);

            $.ajax({
                url: wp_job_board_pro_partners.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wp_job_board_pro_partners_ajax_change_status',
                    post_id: post_id,
                    status: status,
                    nonce: wp_job_board_pro_partners.ajax_nonce,
                }
            }).done(function(response) {
                if (response.status) {
                    $this.closest('tr').find('.column-post_status').html(response.status_label);
                    $this.closest('.partner-status-buttons').find('.partner-status-change').removeClass('button-primary');
                    $this.addClass('button-primary');
                    
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                }
            });
        });
    });
})(jQuery); 