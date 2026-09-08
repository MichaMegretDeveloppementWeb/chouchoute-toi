{{--
    A block of structured data, shaped as a `@graph`.

    The graph rather than a lone node: it is the shape that lets several nodes
    name each other by their `@id`, which is the whole point of the split
    between the layout and the pages.

    Rendered through SiteGraphService::render(), which escapes the angle
    brackets: a « </script> » arriving in a Google review would close the tag,
    and everything after it would become HTML.
--}}
@props(['nodes'])

<script type="application/ld+json">
{!! \App\Services\SiteGraphService::render($nodes) !!}
</script>
