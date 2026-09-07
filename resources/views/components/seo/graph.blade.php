{{--
    Un bloc de donnees structurees, sous la forme d'un `@graph`.

    Le graphe plutot qu'un nœud isole · c'est la forme qui permet a plusieurs
    nœuds de se designer entre eux par leur `@id`, ce qui est tout l'objet du
    decoupage entre le gabarit et les pages.

    Le rendu passe par `SiteGraphService::render()`, qui echappe les chevrons ·
    un « </script> » arrive dans un avis Google fermerait la balise, et tout ce
    qui suit deviendrait du HTML.
--}}
@props(['nodes'])

<script type="application/ld+json">
{!! \App\Services\SiteGraphService::render($nodes) !!}
</script>
