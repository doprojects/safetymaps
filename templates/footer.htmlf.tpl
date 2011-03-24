<div id="footer">
    <p><a href="http://doprojects.org"><img src="{$base_dir}/images/mail-do-logo.png" alt="Do Projects" width="42" height="21"></a> &copy;2011 <a href="http://doprojects.org">Do projects</a>.</p>
    <p>Safety Maps is offered to you under a <a href="http://creativecommons.org/licenses/by-nc-sa/3.0">Creative Commons Attribution-Noncommercial-Share Alike license</a>.</p>
    <p>Map images are &copy;CloudMade and <a href="http://openstreetmap.org">OpenStreetMap.org</a> contributors, used under the <a href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons Attribution-Share Alike license</a>.</p>
</div>

{literal}
<script type='text/javascript'> var mp_protocol = (('https:' == document.location.protocol) ? 'https://' : 'http://'); document.write(unescape('%3Cscript src="' + mp_protocol + 'api.mixpanel.com/site_media/js/api/mixpanel.js" type="text/javascript"%3E%3C/script%3E')); </script> <script type='text/javascript'> try {  var mpmetrics = new MixpanelLib('0xdeadbeef'); } catch(err) { null_fn = function () {}; var mpmetrics = {  track: null_fn,  track_funnel: null_fn,  register: null_fn,  register_once: null_fn, register_funnel: null_fn }; } </script>
<script type='text/javascript'> mpmetrics.track("{/literal}{$eventname|escape:'javascript'}{literal}", {}); </script> 
{/literal}
