let SharedBareModule = (./types-shared-modules.dhall).SharedBareModule

let minimal = [
    , SharedBareModule.Anchors {=}
    , SharedBareModule.BootstrapExtended {=}
    , SharedBareModule.DocExtensions {=}
    , SharedBareModule.DynamicToggle {=}
    , SharedBareModule.Favicons {=}
    , SharedBareModule.Footnotes {=}
    , SharedBareModule.Html {=}
    , SharedBareModule.JsStandardLib {=}
    , SharedBareModule.LocaleDe {=}
    , SharedBareModule.MathjaxExtensions {=}
    , SharedBareModule.NavBuild {=}
    , SharedBareModule.NavCommon {=}
    , SharedBareModule.NavView {=}
    , SharedBareModule.PrintMode {=}
    , SharedBareModule.References {=}
    , SharedBareModule.Source {=}
    , SharedBareModule.SubpagesAll {=}
    , SharedBareModule.SyncDims {=}
    , SharedBareModule.TemplateGeneric {=}
    , SharedBareModule.TemplateInterbook {=}
    , SharedBareModule.TemplateNavigable {=}
    , SharedBareModule.Title {=}

    , SharedBareModule.Bootstrap {=}
    , SharedBareModule.BootstrapIcons {=}
    , SharedBareModule.Mathjax {=}
    , SharedBareModule.PagedJs {=}
    , SharedBareModule.Prism {=}
]
: List SharedBareModule


let full = minimal # [
    , SharedBareModule.DbMysql {=}
    , SharedBareModule.Exercise {=}
    , SharedBareModule.Grading {=}
    , SharedBareModule.Hamster {=}
    , SharedBareModule.IssueTracker {=}
    , SharedBareModule.JavaWorldsDoc {=}
    , SharedBareModule.JsxGraphHelpers {=}
    , SharedBareModule.KlipseWithTauProlog {=}
    , SharedBareModule.Markdown {=}
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

    , SharedBareModule.EskuelSuite {=}
    , SharedBareModule.Fullcalendar {=}
    , SharedBareModule.Mathjs {=}
    , SharedBareModule.Nerdamer {=}
    , SharedBareModule.JsxGraph {=}
    , SharedBareModule.PagedJs {=}
    , SharedBareModule.Parsedown {=}
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