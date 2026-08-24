<?php

namespace QTX\Core\Rest;

final class RestRoutePolicyAdapter {
    private RestRouteRegistry $routes;
    private RestLanguagePolicy $languagePolicy;

    public function __construct( RestRouteRegistry $routes, RestLanguagePolicy $languagePolicy ) {
        $this->routes = $routes;
        $this->languagePolicy = $languagePolicy;
    }

    public function resolve(
        string $route,
        string $method,
        ?string $language,
        string $context,
        bool $rawRequested
    ): ?RestTranslationContext {
        $match = $this->routes->match( $route, $method );
        if ( $match === null ) {
            return null;
        }
        $canEdit = $context === 'edit'
            ? $match['definition']->canEdit( $match['object_id'] )
            : false;

        return $this->languagePolicy->resolve( $language, $context, $rawRequested, $canEdit );
    }
}
