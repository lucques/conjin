let P = ../ext/dhall/dhall-lang-fd057db9b3f89de44cdc77d9669e958b04ed416a/Prelude/package_resolved.dhall

let SharedModuleLocation = { dirName: Text, isExternal: Bool }

let SharedBareModule = <
    Anchors: {} |
    DbMysql: {} |
    DbSqlite: {} |
    DocExtensions: {} |
    Exercise: {} |
    Favicons: {} |
    Footnotes: {} |
    Grading: {} |
    Hamster: {} |
    Html: {} |
    JavaWorldsDoc: {} |
    JsStandardLib: {} |
    JsxGraphHelpers: {} |
    KlipseWithTauProlog: {} |
    LocaleDe: {} |
    Markdown: {} |
    MathArith: {} |
    MathLogic: {} |
    MathProbability: {} |
    MathValueTable: {} |
    MathjaxExtensions: {} |
    NavBuild: {} |
    NavCommon: {} |
    NavView: {} |
    PrintMode: {} |
    References: {} |
    SolMode: {} |
    Source: {} |
    SqlJsExtensions: {} |
    SqlJsKnowledgeTagged: {} |
    SqlJsKnowledgeHistory: {} |
    SubpagesAll: {} |
    SyncDims: {} |
    TemplateBootstrapped: {} |
    TemplateExam: {} |
    TemplateGeneric: {} |
    TemplateInterbook: {} |
    TemplateNavigable: {} |
    Timetable: {} |
    Title: {} |

    Bootstrap: {} |
    BootstrapIcons: {} |
    EskuelSuite: {} |
    Fullcalendar: {} |
    JsxGraph: {} |
    Mathjax: {} |
    Mathjs: {} |
    Nerdamer: {} |
    PagedJs: {} |
    Parsedown: {} |
    Picocss: {} |
    Prism: {} |
    SpinningWheel: {} |
    SqlJs: {}
>

let sharedBareModuleToLocation = \(id: SharedBareModule) ->
    merge {
        Anchors               = \(t: {}) -> { dirName = "anchors",                  isExternal = False },
        DbMysql               = \(t: {}) -> { dirName = "db-mysql",                 isExternal = False },
        DbSqlite              = \(t: {}) -> { dirName = "db-sqlite",                isExternal = False },
        DocExtensions         = \(t: {}) -> { dirName = "doc-extensions",           isExternal = False },
        Exercise              = \(t: {}) -> { dirName = "exercise",                 isExternal = False },
        Favicons              = \(t: {}) -> { dirName = "favicons",                 isExternal = False },
        Footnotes             = \(t: {}) -> { dirName = "footnotes",                isExternal = False },
        Grading               = \(t: {}) -> { dirName = "grading",                  isExternal = False },
        Hamster               = \(t: {}) -> { dirName = "hamster",                  isExternal = False },
        Html                  = \(t: {}) -> { dirName = "html",                     isExternal = False },
        JavaWorldsDoc         = \(t: {}) -> { dirName = "java-worlds-doc",          isExternal = False },
        JsStandardLib         = \(t: {}) -> { dirName = "js-standard-lib",          isExternal = False },
        JsxGraphHelpers       = \(t: {}) -> { dirName = "jsxgraph-helpers",         isExternal = False },
        LocaleDe              = \(t: {}) -> { dirName = "locale-de",                isExternal = False },
        Markdown              = \(t: {}) -> { dirName = "markdown",                 isExternal = False },
        MathArith             = \(t: {}) -> { dirName = "math-arith",               isExternal = False },
        MathLogic             = \(t: {}) -> { dirName = "math-logic",               isExternal = False },
        MathProbability       = \(t: {}) -> { dirName = "math-probability",         isExternal = False },  
        MathValueTable        = \(t: {}) -> { dirName = "math-value-table",         isExternal = False },
        MathjaxExtensions     = \(t: {}) -> { dirName = "mathjax-extensions",       isExternal = False },
        NavBuild              = \(t: {}) -> { dirName = "nav-build",                isExternal = False },
        NavCommon             = \(t: {}) -> { dirName = "nav-common",               isExternal = False },
        NavView               = \(t: {}) -> { dirName = "nav-view",                 isExternal = False },
        PrintMode             = \(t: {}) -> { dirName = "print-mode",               isExternal = False },
        References            = \(t: {}) -> { dirName = "references",               isExternal = False },
        SolMode               = \(t: {}) -> { dirName = "sol-mode",                 isExternal = False },
        Source                = \(t: {}) -> { dirName = "source",                   isExternal = False },
        SqlJsExtensions       = \(t: {}) -> { dirName = "sql-js-extensions",        isExternal = False },
        SqlJsKnowledgeTagged  = \(t: {}) -> { dirName = "sql-js-knowledge-tagged",  isExternal = False },
        SqlJsKnowledgeHistory = \(t: {}) -> { dirName = "sql-js-knowledge-history", isExternal = False },
        SubpagesAll           = \(t: {}) -> { dirName = "subpages-all",             isExternal = False },
        SyncDims              = \(t: {}) -> { dirName = "sync-dims",                isExternal = False },
        TemplateBootstrapped  = \(t: {}) -> { dirName = "template-bootstrapped",    isExternal = False },
        TemplateExam          = \(t: {}) -> { dirName = "template-exam",            isExternal = False },
        TemplateGeneric       = \(t: {}) -> { dirName = "template-generic",         isExternal = False },
        TemplateInterbook     = \(t: {}) -> { dirName = "template-interbook",       isExternal = False },
        TemplateNavigable     = \(t: {}) -> { dirName = "template-navigable",       isExternal = False },
        Timetable             = \(t: {}) -> { dirName = "timetable",                isExternal = False },
        Title                 = \(t: {}) -> { dirName = "title",                    isExternal = False },

        Bootstrap             = \(t: {}) -> { dirName = "bootstrap",                isExternal = True },
        BootstrapIcons        = \(t: {}) -> { dirName = "bootstrap-icons",          isExternal = True },
        EskuelSuite           = \(t: {}) -> { dirName = "eskuel-suite",             isExternal = True },
        Fullcalendar          = \(t: {}) -> { dirName = "fullcalendar",             isExternal = True },
        KlipseWithTauProlog   = \(t: {}) -> { dirName = "klipse-with-tau-prolog",   isExternal = True },
        Mathjax               = \(t: {}) -> { dirName = "mathjax",                  isExternal = True },
        Mathjs                = \(t: {}) -> { dirName = "mathjs",                   isExternal = True },
        Nerdamer              = \(t: {}) -> { dirName = "nerdamer",                 isExternal = True },
        JsxGraph              = \(t: {}) -> { dirName = "jsxgraph",                 isExternal = True },
        PagedJs               = \(t: {}) -> { dirName = "paged-js",                 isExternal = True },
        Parsedown             = \(t: {}) -> { dirName = "parsedown",                isExternal = True },
        Picocss               = \(t: {}) -> { dirName = "picocss",                  isExternal = True },
        Prism                 = \(t: {}) -> { dirName = "prism",                    isExternal = True },
        SpinningWheel         = \(t: {}) -> { dirName = "spinning-wheel",           isExternal = True },
        SqlJs                 = \(t: {}) -> { dirName = "sql-js",                   isExternal = True },
    } id
: SharedModuleLocation


let sharedBareModuleToScssDeps = \(id: SharedBareModule) ->
    merge {
        Anchors               = \(t: {}) -> P.List.empty SharedModuleLocation,
        DbMysql               = \(t: {}) -> P.List.empty SharedModuleLocation,
        DbSqlite              = \(t: {}) -> P.List.empty SharedModuleLocation,
        DocExtensions         = \(t: {}) -> P.List.empty SharedModuleLocation,
        Exercise              = \(t: {}) -> P.List.empty SharedModuleLocation,
        Favicons              = \(t: {}) -> P.List.empty SharedModuleLocation,
        Footnotes             = \(t: {}) -> P.List.empty SharedModuleLocation,
        Grading               = \(t: {}) -> P.List.empty SharedModuleLocation,
        Hamster               = \(t: {}) -> P.List.empty SharedModuleLocation,
        Html                  = \(t: {}) -> P.List.empty SharedModuleLocation,
        JavaWorldsDoc         = \(t: {}) -> P.List.empty SharedModuleLocation,
        JsStandardLib         = \(t: {}) -> P.List.empty SharedModuleLocation,
        JsxGraphHelpers       = \(t: {}) -> P.List.empty SharedModuleLocation,
        KlipseWithTauProlog   = \(t: {}) -> P.List.empty SharedModuleLocation,
        LocaleDe              = \(t: {}) -> P.List.empty SharedModuleLocation,
        Markdown              = \(t: {}) -> P.List.empty SharedModuleLocation,
        MathArith             = \(t: {}) -> P.List.empty SharedModuleLocation,
        MathLogic             = \(t: {}) -> P.List.empty SharedModuleLocation,
        MathProbability       = \(t: {}) -> P.List.empty SharedModuleLocation,
        MathValueTable        = \(t: {}) -> P.List.empty SharedModuleLocation,
        MathjaxExtensions     = \(t: {}) -> P.List.empty SharedModuleLocation,
        NavBuild              = \(t: {}) -> P.List.empty SharedModuleLocation,
        NavCommon             = \(t: {}) -> P.List.empty SharedModuleLocation,
        NavView               = \(t: {}) -> P.List.empty SharedModuleLocation,
        PrintMode             = \(t: {}) -> P.List.empty SharedModuleLocation,
        References            = \(t: {}) -> P.List.empty SharedModuleLocation,
        SolMode               = \(t: {}) -> P.List.empty SharedModuleLocation,
        Source                = \(t: {}) -> P.List.empty SharedModuleLocation,
        SqlJsExtensions       = \(t: {}) -> P.List.empty SharedModuleLocation,
        SqlJsKnowledgeTagged  = \(t: {}) -> P.List.empty SharedModuleLocation,
        SqlJsKnowledgeHistory = \(t: {}) -> P.List.empty SharedModuleLocation,
        SubpagesAll           = \(t: {}) -> P.List.empty SharedModuleLocation,
        SyncDims              = \(t: {}) -> P.List.empty SharedModuleLocation,
        TemplateBootstrapped  = \(t: {}) -> [
                                    , sharedBareModuleToLocation (SharedBareModule.Bootstrap {=})
                                ],
        TemplateExam          = \(t: {}) -> [
                                    , sharedBareModuleToLocation (SharedBareModule.Bootstrap {=})
                                    , sharedBareModuleToLocation (SharedBareModule.TemplateBootstrapped {=})
                                    , sharedBareModuleToLocation (SharedBareModule.TemplateNavigable {=})
                                ],
        TemplateGeneric       = \(t: {}) -> P.List.empty SharedModuleLocation,
        TemplateInterbook     = \(t: {}) -> [
                                    , sharedBareModuleToLocation (SharedBareModule.Bootstrap {=})
                                    , sharedBareModuleToLocation (SharedBareModule.TemplateBootstrapped {=})
                                    , sharedBareModuleToLocation (SharedBareModule.TemplateNavigable {=})
                                ],
        TemplateNavigable     = \(t: {}) -> [
                                    , sharedBareModuleToLocation (SharedBareModule.Bootstrap {=})
                                ],
        Timetable             = \(t: {}) -> P.List.empty SharedModuleLocation,
        Title                 = \(t: {}) -> P.List.empty SharedModuleLocation,
        Bootstrap             = \(t: {}) -> P.List.empty SharedModuleLocation,
        BootstrapIcons        = \(t: {}) -> P.List.empty SharedModuleLocation,
        EskuelSuite           = \(t: {}) -> P.List.empty SharedModuleLocation,
        Fullcalendar          = \(t: {}) -> P.List.empty SharedModuleLocation,
        Mathjax               = \(t: {}) -> P.List.empty SharedModuleLocation,
        Mathjs                = \(t: {}) -> P.List.empty SharedModuleLocation,
        Nerdamer              = \(t: {}) -> P.List.empty SharedModuleLocation,
        JsxGraph              = \(t: {}) -> P.List.empty SharedModuleLocation,
        PagedJs               = \(t: {}) -> P.List.empty SharedModuleLocation,
        Parsedown             = \(t: {}) -> P.List.empty SharedModuleLocation,
        Picocss               = \(t: {}) -> P.List.empty SharedModuleLocation,
        Prism                 = \(t: {}) -> P.List.empty SharedModuleLocation,
        SpinningWheel         = \(t: {}) -> P.List.empty SharedModuleLocation,
        SqlJs                 = \(t: {}) -> P.List.empty SharedModuleLocation,
    } id
: List SharedModuleLocation



in

{
    SharedModuleLocation,
    SharedBareModule,
    sharedBareModuleToLocation,
    sharedBareModuleToScssDeps
}