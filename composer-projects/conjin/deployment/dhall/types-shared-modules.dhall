let P = ../../ext/dhall/dhall-lang/Prelude/package_resolved.dhall

let SharedModuleLocation = { dirName: Text, isExternal: Bool }

let SharedBareModule = <
    Anchors: {} |
    BootstrapAlternative: {} |
    BootstrapExtended: {} |
    DbMysql: {} |
    DocExtensions: {} |
    DynamicToggle: {} |
    Exercise: {} |
    Favicons: {} |
    Footnotes: {} |
    Grading: {} |
    Hamster: {} |
    Html: {} |
    IssueTracker: {} |
    JavaWorlds: {} |
    JsStandardLib: {} |
    JsxGraphHelpers: {} |
    LocaleDe: {} |
    Localization: {} |
    Markdown: {} |
    MathArith: {} |
    MathLogic: {} |
    MathProbability: {} |
    MathValueTable: {} |
    MathjaxExtensions: {} |
    NavBuild: {} |
    NavCommon: {} |
    NavView: {} |
    Nuggets: {} |
    PrintMode: {} |
    References: {} |
    SolMode: {} |
    Source: {} |
    SqlJsExtensions: {} |
    SqlJsKnowledgeTagged: {} |
    SqlJsKnowledgeHistory: {} |
    SubpagesAll: {} |
    SyncDims: {} |
    TemplateExam: {} |
    TemplateGeneric: {} |
    TemplateInterbook: {} |
    TemplateNavigable: {} |
    Timetable: {} |
    Title: {} |
    GraphingTypes: {} |

    Bootstrap: {} |
    BootstrapIcons: {} |
    ChartJs: {} |
    Fullcalendar: {} |
    JsxGraph: {} |
    Mathjax: {} |
    Mathjs: {} |
    Nerdamer: {} |
    PagedJs: {} |
    Picocss: {} |
    Prism: {} |
    SpinningWheel: {} |
    SqlJs: {} |
    TomSelect: {}
>

let sharedBareModuleToLocation = \(id: SharedBareModule) ->
    merge {
        Anchors                = \(t: {}) -> { dirName = "anchors",                  isExternal = False },
        BootstrapAlternative   = \(t: {}) -> { dirName = "bootstrap-alternative",    isExternal = False },
        BootstrapExtended      = \(t: {}) -> { dirName = "bootstrap-extended",       isExternal = False },
        DbMysql                = \(t: {}) -> { dirName = "db-mysql",                 isExternal = False },
        DocExtensions          = \(t: {}) -> { dirName = "doc-extensions",           isExternal = False },
        DynamicToggle          = \(t: {}) -> { dirName = "dynamic-toggle",           isExternal = False },
        Exercise               = \(t: {}) -> { dirName = "exercise",                 isExternal = False },
        Favicons               = \(t: {}) -> { dirName = "favicons",                 isExternal = False },
        Footnotes              = \(t: {}) -> { dirName = "footnotes",                isExternal = False },
        Grading                = \(t: {}) -> { dirName = "grading",                  isExternal = False },
        Hamster                = \(t: {}) -> { dirName = "hamster",                  isExternal = False },
        Html                   = \(t: {}) -> { dirName = "html",                     isExternal = False },
        IssueTracker           = \(t: {}) -> { dirName = "issue-tracker",            isExternal = False },
        JavaWorlds             = \(t: {}) -> { dirName = "java-worlds",              isExternal = False },
        JsStandardLib          = \(t: {}) -> { dirName = "js-standard-lib",          isExternal = False },
        JsxGraphHelpers        = \(t: {}) -> { dirName = "jsxgraph-helpers",         isExternal = False },
        LocaleDe               = \(t: {}) -> { dirName = "locale-de",                isExternal = False },
        Localization           = \(t: {}) -> { dirName = "localization",             isExternal = False },
        Markdown               = \(t: {}) -> { dirName = "markdown",                 isExternal = False },
        MathArith              = \(t: {}) -> { dirName = "math-arith",               isExternal = False },
        MathLogic              = \(t: {}) -> { dirName = "math-logic",               isExternal = False },
        MathProbability        = \(t: {}) -> { dirName = "math-probability",         isExternal = False },  
        MathValueTable         = \(t: {}) -> { dirName = "math-value-table",         isExternal = False },
        MathjaxExtensions      = \(t: {}) -> { dirName = "mathjax-extensions",       isExternal = False },
        NavBuild               = \(t: {}) -> { dirName = "nav-build",                isExternal = False },
        NavCommon              = \(t: {}) -> { dirName = "nav-common",               isExternal = False },
        NavView                = \(t: {}) -> { dirName = "nav-view",                 isExternal = False },
        Nuggets                = \(t: {}) -> { dirName = "nuggets",                  isExternal = False },
        PrintMode              = \(t: {}) -> { dirName = "print-mode",               isExternal = False },
        References             = \(t: {}) -> { dirName = "references",               isExternal = False },
        SolMode                = \(t: {}) -> { dirName = "sol-mode",                 isExternal = False },
        Source                 = \(t: {}) -> { dirName = "source",                   isExternal = False },
        SqlJsExtensions        = \(t: {}) -> { dirName = "sql-js-extensions",        isExternal = False },
        SqlJsKnowledgeTagged   = \(t: {}) -> { dirName = "sql-js-knowledge-tagged",  isExternal = False },
        SqlJsKnowledgeHistory  = \(t: {}) -> { dirName = "sql-js-knowledge-history", isExternal = False },
        SubpagesAll            = \(t: {}) -> { dirName = "subpages-all",             isExternal = False },
        SyncDims               = \(t: {}) -> { dirName = "sync-dims",                isExternal = False },
        TemplateExam           = \(t: {}) -> { dirName = "template-exam",            isExternal = False },
        TemplateGeneric        = \(t: {}) -> { dirName = "template-generic",         isExternal = False },
        TemplateInterbook      = \(t: {}) -> { dirName = "template-interbook",       isExternal = False },
        TemplateNavigable      = \(t: {}) -> { dirName = "template-navigable",       isExternal = False },
        Timetable              = \(t: {}) -> { dirName = "timetable",                isExternal = False },
        Title                  = \(t: {}) -> { dirName = "title",                    isExternal = False },
        GraphingTypes          = \(t: {}) -> { dirName = "graphing-types",           isExternal = False },

        Bootstrap              = \(t: {}) -> { dirName = "bootstrap",                isExternal = True },
        BootstrapIcons         = \(t: {}) -> { dirName = "bootstrap-icons",          isExternal = True },
        ChartJs                = \(t: {}) -> { dirName = "chart-js",                 isExternal = True },
        Fullcalendar           = \(t: {}) -> { dirName = "fullcalendar",             isExternal = True },
        Mathjax                = \(t: {}) -> { dirName = "mathjax",                  isExternal = True },
        Mathjs                 = \(t: {}) -> { dirName = "mathjs",                   isExternal = True },
        Nerdamer               = \(t: {}) -> { dirName = "nerdamer",                 isExternal = True },
        JsxGraph               = \(t: {}) -> { dirName = "jsxgraph",                 isExternal = True },
        PagedJs                = \(t: {}) -> { dirName = "paged-js",                 isExternal = True },
        Picocss                = \(t: {}) -> { dirName = "picocss",                  isExternal = True },
        Prism                  = \(t: {}) -> { dirName = "prism",                    isExternal = True },
        SpinningWheel          = \(t: {}) -> { dirName = "spinning-wheel",           isExternal = True },
        SqlJs                  = \(t: {}) -> { dirName = "sql-js",                   isExternal = True },
        TomSelect              = \(t: {}) -> { dirName = "tom-select",               isExternal = True },
    } id
: SharedModuleLocation


let sharedBareModuleToScssDeps = \(id: SharedBareModule) ->
    merge {
        Anchors                = \(t: {}) -> P.List.empty SharedModuleLocation,
        BootstrapAlternative   = \(t: {}) -> P.List.empty SharedModuleLocation,
        BootstrapExtended      = \(t: {}) -> [
                                     , sharedBareModuleToLocation (SharedBareModule.Bootstrap {=})
                                 ],
        DbMysql                = \(t: {}) -> P.List.empty SharedModuleLocation,
        DocExtensions          = \(t: {}) -> P.List.empty SharedModuleLocation,
        DynamicToggle          = \(t: {}) -> P.List.empty SharedModuleLocation,
        Exercise               = \(t: {}) -> P.List.empty SharedModuleLocation,
        Favicons               = \(t: {}) -> P.List.empty SharedModuleLocation,
        Footnotes              = \(t: {}) -> P.List.empty SharedModuleLocation,
        Grading                = \(t: {}) -> P.List.empty SharedModuleLocation,
        Hamster                = \(t: {}) -> P.List.empty SharedModuleLocation,
        Html                   = \(t: {}) -> P.List.empty SharedModuleLocation,
        IssueTracker           = \(t: {}) -> P.List.empty SharedModuleLocation,
        JavaWorlds             = \(t: {}) -> P.List.empty SharedModuleLocation,
        JsStandardLib          = \(t: {}) -> P.List.empty SharedModuleLocation,
        JsxGraphHelpers        = \(t: {}) -> P.List.empty SharedModuleLocation,
        LocaleDe               = \(t: {}) -> P.List.empty SharedModuleLocation,
        Localization           = \(t: {}) -> P.List.empty SharedModuleLocation,
        Markdown               = \(t: {}) -> P.List.empty SharedModuleLocation,
        MathArith              = \(t: {}) -> P.List.empty SharedModuleLocation,
        MathLogic              = \(t: {}) -> P.List.empty SharedModuleLocation,
        MathProbability        = \(t: {}) -> P.List.empty SharedModuleLocation,
        MathValueTable         = \(t: {}) -> P.List.empty SharedModuleLocation,
        MathjaxExtensions      = \(t: {}) -> P.List.empty SharedModuleLocation,
        NavBuild               = \(t: {}) -> P.List.empty SharedModuleLocation,
        NavCommon              = \(t: {}) -> P.List.empty SharedModuleLocation,
        NavView                = \(t: {}) -> P.List.empty SharedModuleLocation,
        Nuggets                = \(t: {}) -> P.List.empty SharedModuleLocation,
        PrintMode              = \(t: {}) -> P.List.empty SharedModuleLocation,
        References             = \(t: {}) -> P.List.empty SharedModuleLocation,
        SolMode                = \(t: {}) -> P.List.empty SharedModuleLocation,
        Source                 = \(t: {}) -> P.List.empty SharedModuleLocation,
        SqlJsExtensions        = \(t: {}) -> P.List.empty SharedModuleLocation,
        SqlJsKnowledgeTagged   = \(t: {}) -> P.List.empty SharedModuleLocation,
        SqlJsKnowledgeHistory  = \(t: {}) -> P.List.empty SharedModuleLocation,
        SubpagesAll            = \(t: {}) -> P.List.empty SharedModuleLocation,
        SyncDims               = \(t: {}) -> P.List.empty SharedModuleLocation,
        TemplateExam           = \(t: {}) -> [
                                     , sharedBareModuleToLocation (SharedBareModule.Bootstrap {=})
                                     , sharedBareModuleToLocation (SharedBareModule.BootstrapExtended {=})
                                     , sharedBareModuleToLocation (SharedBareModule.TemplateNavigable {=})
                                 ],
        TemplateGeneric        = \(t: {}) -> P.List.empty SharedModuleLocation,
        TemplateInterbook      = \(t: {}) -> [
                                     , sharedBareModuleToLocation (SharedBareModule.Bootstrap {=})
                                     , sharedBareModuleToLocation (SharedBareModule.BootstrapExtended {=})
                                     , sharedBareModuleToLocation (SharedBareModule.TemplateNavigable {=})
                                 ],
        TemplateNavigable      = \(t: {}) -> [
                                     , sharedBareModuleToLocation (SharedBareModule.Bootstrap {=})
                                     , sharedBareModuleToLocation (SharedBareModule.BootstrapExtended {=})
                                 ],
        Timetable              = \(t: {}) -> P.List.empty SharedModuleLocation,
        Title                  = \(t: {}) -> P.List.empty SharedModuleLocation,
        GraphingTypes          = \(t: {}) -> P.List.empty SharedModuleLocation,
        Bootstrap              = \(t: {}) -> P.List.empty SharedModuleLocation,
        BootstrapIcons         = \(t: {}) -> P.List.empty SharedModuleLocation,
        ChartJs                = \(t: {}) -> P.List.empty SharedModuleLocation,
        Fullcalendar           = \(t: {}) -> P.List.empty SharedModuleLocation,
        Mathjax                = \(t: {}) -> P.List.empty SharedModuleLocation,
        Mathjs                 = \(t: {}) -> P.List.empty SharedModuleLocation,
        Nerdamer               = \(t: {}) -> P.List.empty SharedModuleLocation,
        JsxGraph               = \(t: {}) -> P.List.empty SharedModuleLocation,
        PagedJs                = \(t: {}) -> P.List.empty SharedModuleLocation,
        Picocss                = \(t: {}) -> P.List.empty SharedModuleLocation,
        Prism                  = \(t: {}) -> P.List.empty SharedModuleLocation,
        SpinningWheel          = \(t: {}) -> P.List.empty SharedModuleLocation,
        SqlJs                  = \(t: {}) -> P.List.empty SharedModuleLocation,
        TomSelect              = \(t: {}) -> P.List.empty SharedModuleLocation,
    } id
: List SharedModuleLocation



in

{
    SharedModuleLocation,
    SharedBareModule,
    sharedBareModuleToLocation,
    sharedBareModuleToScssDeps
}
