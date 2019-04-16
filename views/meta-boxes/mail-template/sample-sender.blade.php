<p class="post-attributes-label-wrapper">
    <label for="sample-to-address">Send Sample To:</label>
</p>
<input style="width: 100%" id="sample-to-address" type="email" placeholder="Email address" value="{!! wp_get_current_user()->user_email !!}">
<div style="margin-top: 10px; text-align: right; width: 100%">
    <button id="send-sample-email-button" class="button button-primary">Send Sample Email</button>
</div>
<script>
    (function($){
        $('#send-sample-email-button').on('click', function(e) {
            e.preventDefault();

            let data = {
                nonce: '{!! wp_create_nonce('send-sample-email') !!}',
                email: $('#sample-to-address').val(),
                post: '{!! $post->id !!}',
                action: 'send_sample_email'
            };

            $.post(ajaxurl, data, function(response) {
                alert('Sample email sent.');
            });
            return false;
        });
    })(jQuery);
</script>