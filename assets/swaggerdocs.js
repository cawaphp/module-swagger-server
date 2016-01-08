$(document).ready(function() {
    $('pre.highlight code').each(function(i, block) {
        hljs.highlightBlock(block);
    });
});
