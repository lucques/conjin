{
    , prelude                 = ../../ext/dhall/dhall-lang/Prelude/package_resolved.dhall

    , types                   = ./types.dhall
    , typesSharedModules      = ./types-shared-modules.dhall
    , typesTagged             = ./types-tagged.dhall

    , dockerImages            = ./docker-images.dhall
    , artifactsDockerCommon   = ./artifacts-docker-common.dhall
    , artifactsLocal          = ./artifacts-local.dhall
    , artifactsTest           = ./artifacts-test.dhall
    , artifactsRemote         = ./artifacts-remote.dhall
    , artifactsHtdocs         = ./artifacts-htdocs.dhall

    , tools                   = ./tools.dhall

    , sharedModuleCollections = ./shared-module-collections.dhall
}
