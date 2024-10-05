let SharedBareModule = (./types-shared-modules.dhall).SharedBareModule

let full = [
    , SharedBareModule.Anchors {=}
    , SharedBareModule.DbMysql {=}
    , SharedBareModule.DbSqlite {=}
    , SharedBareModule.DocExtensions {=}
    , SharedBareModule.Exercise {=}
    , SharedBareModule.Favicons {=}
    , SharedBareModule.Footnotes {=}
    , SharedBareModule.Grading {=}
    , SharedBareModule.Hamster {=}
    , SharedBareModule.Html {=}
    , SharedBareModule.JavaWorldsDoc {=}
    , SharedBareModule.JsStandardLib {=}
    , SharedBareModule.JsxGraphHelpers {=}
    , SharedBareModule.KlipseWithTauProlog {=}
    , SharedBareModule.LocaleDe {=}
    , SharedBareModule.Markdown {=}
    , SharedBareModule.MathArith {=}
    , SharedBareModule.MathLogic {=}
    , SharedBareModule.MathProbability {=}
    , SharedBareModule.MathValueTable {=}
    , SharedBareModule.MathjaxExtensions {=}
    , SharedBareModule.NavBuild {=}
    , SharedBareModule.NavCommon {=}
    , SharedBareModule.NavView {=}
    , SharedBareModule.PrintMode {=}
    , SharedBareModule.References {=}
    , SharedBareModule.SolMode {=}
    , SharedBareModule.Source {=}
    , SharedBareModule.SqlJsExtensions {=}
    , SharedBareModule.SqlJsKnowledgeTagged {=}
    , SharedBareModule.SqlJsKnowledgeHistory {=}
    , SharedBareModule.SubpagesAll {=}
    , SharedBareModule.SyncDims {=}
    , SharedBareModule.TemplateBootstrapped {=}
    , SharedBareModule.TemplateExam {=}
    , SharedBareModule.TemplateGeneric {=}
    , SharedBareModule.TemplateInterbook {=}
    , SharedBareModule.TemplateNavigable {=}
    , SharedBareModule.Timetable {=}
    , SharedBareModule.Title {=}
    , SharedBareModule.Bootstrap {=}
    , SharedBareModule.BootstrapIcons {=}
    , SharedBareModule.EskuelSuite {=}
    , SharedBareModule.Fullcalendar {=}
    , SharedBareModule.Mathjax {=}
    , SharedBareModule.Mathjs {=}
    , SharedBareModule.Nerdamer {=}
    , SharedBareModule.JsxGraph {=}
    , SharedBareModule.PagedJs {=}
    , SharedBareModule.Parsedown {=}
    , SharedBareModule.Picocss {=}
    , SharedBareModule.Prism {=}
    , SharedBareModule.SpinningWheel {=}
    , SharedBareModule.SqlJs {=}
]
: List SharedBareModule

let minimal = [
    , SharedBareModule.Anchors {=}
    , SharedBareModule.DocExtensions {=}
    , SharedBareModule.DbSqlite {=}
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
    , SharedBareModule.TemplateBootstrapped {=}
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

in

{
    full,
    minimal
}