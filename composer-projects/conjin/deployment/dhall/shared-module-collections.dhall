let SharedBareModule = (./types-shared-modules.dhall).SharedBareModule

let minimal = [
    -- Local
    , SharedBareModule.Anchors {=}
    , SharedBareModule.BootstrapAlternative {=}
    , SharedBareModule.BootstrapExtended {=}
    , SharedBareModule.DocExtensions {=}
    , SharedBareModule.DynamicToggle {=}
    , SharedBareModule.Favicons {=}
    , SharedBareModule.Footnotes {=}
    , SharedBareModule.Html {=}
    , SharedBareModule.JsStandardLib {=}
    , SharedBareModule.LocaleDe {=}
    , SharedBareModule.Localization {=}
    , SharedBareModule.MathjaxExtensions {=}
    , SharedBareModule.Markdown {=}
    , SharedBareModule.NavBuild {=}
    , SharedBareModule.NavCommon {=}
    , SharedBareModule.NavView {=}
    , SharedBareModule.Nuggets {=}
    , SharedBareModule.PrintMode {=}
    , SharedBareModule.References {=}
    , SharedBareModule.Source {=}
    , SharedBareModule.SubpagesAll {=}
    , SharedBareModule.SyncDims {=}
    , SharedBareModule.TemplateGeneric {=}
    , SharedBareModule.Title {=}

    -- External
    , SharedBareModule.Bootstrap {=}
    , SharedBareModule.BootstrapIcons {=}
    , SharedBareModule.Mathjax {=}
    , SharedBareModule.PagedJs {=}
    , SharedBareModule.Prism {=}
]
: List SharedBareModule


let full = minimal # [
    -- Local
    , SharedBareModule.DbMysql {=}
    , SharedBareModule.Exercise {=}
    , SharedBareModule.Grading {=}
    , SharedBareModule.GraphingTypes {=}
    , SharedBareModule.Hamster {=}
    , SharedBareModule.IssueTracker {=}
    , SharedBareModule.JavaWorlds {=}
    , SharedBareModule.JsxGraphHelpers {=}
    , SharedBareModule.MathArith {=}
    , SharedBareModule.MathLogic {=}
    , SharedBareModule.MathProbability {=}
    , SharedBareModule.MathValueTable {=}
    , SharedBareModule.SolMode {=}
    , SharedBareModule.SqlJsExtensions {=}
    , SharedBareModule.SqlJsKnowledgeTagged {=}
    , SharedBareModule.SqlJsKnowledgeHistory {=}
    , SharedBareModule.Timetable {=}
    , SharedBareModule.TemplateExam {=}
    , SharedBareModule.TemplateInterbook {=}
    , SharedBareModule.TemplateNavigable {=}

    -- External
    , SharedBareModule.ChartJs {=}
    , SharedBareModule.Fullcalendar {=}
    , SharedBareModule.Mathjs {=}
    , SharedBareModule.Nerdamer {=}
    , SharedBareModule.JsxGraph {=}
    , SharedBareModule.PagedJs {=}
    , SharedBareModule.Picocss {=}
    , SharedBareModule.SpinningWheel {=}
    , SharedBareModule.SqlJs {=}
    , SharedBareModule.TomSelect {=}
]
: List SharedBareModule

in

{
    full,
    minimal
}
