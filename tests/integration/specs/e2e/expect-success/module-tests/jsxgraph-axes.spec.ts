import { expect, test } from "../../../../fixtures/page-health";

test("resolves axes independently from shared tick and grid spacing", async ({ healthyPage }) => {
  const { page } = healthyPage;
  const response = await page.goto("/module-tests-expect-success/jsxgraph-helpers/?axes=1");
  expect(response?.ok()).toBe(true);
  await expect(page.locator(".jsxgraph-plot")).toHaveCount(12);

  const result = await page.evaluate(() => {
    type Element = {
      elType: string;
      name: string;
      plaintext: string;
      evalVisProp: (name: string) => any;
      dataX: number[];
      dataY: number[];
      minorGrid?: Element;
    };
    type Ticks = Element & {
      getDistanceMajorTicks: () => number;
      labels: Array<Element | null>;
    };
    type Axis = Element & { defaultTicks: Ticks };
    type Board = {
      defaultAxes: Record<string, Axis>;
      grids: Element[];
      objectsList: Element[];
      infobox: Element;
      setBoundingBox: (bounds: number[], keepAspectRatio: boolean) => void;
      fullUpdate: () => void;
    };
    const cases = (window as unknown as {
      jsxAxesCases: Record<string, { board: Board; axes: Array<Element | null>; ticks: Array<Ticks | null> }>;
    }).jsxAxesCases;
    const gridSteps = (key: string) => cases[key].board.grids[0].evalVisProp("majorstep");
    const visibleTexts = (key: string) => cases[key].board.objectsList
      .filter((element) => element !== cases[key].board.infobox && element.elType === "text" && element.evalVisProp("visible"))
      .map((element) => element.plaintext);
    const x = cases.fixed.board.defaultAxes.x;
    const y = cases.fixed.board.defaultAxes.y;
    const beforeZoom = gridSteps("autoVisible");
    const autoHiddenBefore = gridSteps("autoHidden");
    for (const key of ["autoVisible", "autoHidden", "fixed", "gridOnly"]) {
      cases[key].board.setBoundingBox([-40, 25, 40, -25], false);
      cases[key].board.fullUpdate();
    }
    const fixedGrid = cases.fixed.board.grids[0];
    const hiddenGrid = cases.gridOnly.board.grids[0];
    const coordinateData = (element: Element) => [element.dataX, element.dataY]
      .map((values) => values.map((value) => Number.isFinite(value) ? value : null));
    return {
      fixed: {
        label: x.name,
        thickness: x.evalVisProp("strokewidth"),
        fontSize: x.defaultTicks.evalVisProp("label.fontsize"),
        spacing: x.defaultTicks.getDistanceMajorTicks(),
        minors: x.defaultTicks.evalVisProp("minorticks"),
        formatted: x.defaultTicks.labels.some((label) => label?.plaintext.endsWith(" s")),
        yVisible: y.evalVisProp("visible"),
        yTicksVisible: y.defaultTicks.evalVisProp("visible"),
        gridSteps: gridSteps("fixed"),
        gridMinorElements: fixedGrid.evalVisProp("minorelements"),
        hasMinorGrid: fixedGrid.minorGrid?.evalVisProp("face") === "line" && fixedGrid.minorGrid.dataX.length > 0,
      },
      hidden: {
        axesVisible: Object.values(cases.gridOnly.board.defaultAxes).map((axis) => axis.evalVisProp("visible")),
        texts: visibleTexts("gridOnly"),
        gridSteps: gridSteps("gridOnly"),
        sameGridGeometry: JSON.stringify(coordinateData(fixedGrid)) === JSON.stringify(coordinateData(hiddenGrid)),
      },
      automatic: {
        beforeZoom,
        hiddenBefore: autoHiddenBefore,
        afterZoom: gridSteps("autoVisible"),
        hiddenAfter: gridSteps("autoHidden"),
        tickSpacing: ["x", "y"].map((coordinate) => cases.autoVisible.board.defaultAxes[coordinate].defaultTicks.getDistanceMajorTicks()),
      },
      noGrid: {
        gridCount: cases.noGrid.board.grids.length,
        xVisible: cases.noGrid.board.defaultAxes.x.evalVisProp("visible"),
        xLabel: cases.noGrid.board.defaultAxes.x.evalVisProp("withlabel"),
        xTicksVisible: cases.noGrid.board.defaultAxes.x.defaultTicks.evalVisProp("visible"),
        xNumbers: cases.noGrid.board.defaultAxes.x.defaultTicks.evalVisProp("drawlabels"),
        xMinors: cases.noGrid.board.defaultAxes.x.defaultTicks.evalVisProp("minorticks"),
        yTicksVisible: cases.noGrid.board.defaultAxes.y.defaultTicks.evalVisProp("visible"),
      },
      sequence: {
        label: cases.sequence.board.defaultAxes.x.name,
        spacing: cases.sequence.board.defaultAxes.x.defaultTicks.getDistanceMajorTicks(),
        minors: cases.sequence.board.defaultAxes.x.defaultTicks.evalVisProp("minorticks"),
      },
      dynamic: {
        axes: cases.dynamic.axes.map((axis) => axis !== null),
        ticks: cases.dynamic.ticks.map((ticks) => ticks !== null),
        thickness: cases.dynamic.axes[2]?.evalVisProp("strokewidth"),
        spacing: cases.dynamic.ticks[2]?.evalVisProp("ticksdistance"),
        hiddenAxes: cases.dynamicHidden.axes,
        hiddenTicks: cases.dynamicHidden.ticks,
        noNumbers: cases.dynamicNoNumbers.ticks[2]?.evalVisProp("drawlabels"),
        noNumberLabels: cases.dynamicNoNumbers.ticks[2]?.labels?.length ?? 0,
      },
      cavalier: {
        gridCount: cases.cavalier.board.grids.length,
        axes: cases.cavalier.board.objectsList.filter((element) => element.elType === "line" && element.evalVisProp("lastarrow")).length,
        texts: visibleTexts("cavalier"),
        hiddenTexts: visibleTexts("cavalierGrid"),
        backgroundGridSteps: gridSteps("cavalierGrid"),
        noNumbersTexts: visibleTexts("cavalierNoNumbers"),
        sameTickCount: cases.cavalier.board.objectsList.filter((element) => element.elType === "line").length
          === cases.cavalierNoNumbers.board.objectsList.filter((element) => element.elType === "line").length,
      },
    };
  });

  expect(result.fixed).toEqual({
    label: "t's", thickness: 3, fontSize: 16, spacing: 2, minors: 3, formatted: true,
    yVisible: false, yTicksVisible: false, gridSteps: [2, 1], gridMinorElements: [3, 4], hasMinorGrid: true,
  });
  expect(result.hidden).toEqual({ axesVisible: [false, false], texts: [], gridSteps: [2, 1], sameGridGeometry: true });
  expect(result.automatic.beforeZoom.every((value: number) => Number.isFinite(value) && value > 0)).toBe(true);
  expect(result.automatic.afterZoom.every((value: number) => Number.isFinite(value) && value > 0)).toBe(true);
  expect(result.automatic.beforeZoom).toEqual(result.automatic.hiddenBefore);
  expect(result.automatic.afterZoom).toEqual(result.automatic.hiddenAfter);
  expect(result.automatic.afterZoom).toEqual(result.automatic.tickSpacing);
  expect(result.automatic.afterZoom).not.toEqual(result.automatic.beforeZoom);
  expect(result.noGrid).toEqual({ gridCount: 0, xVisible: true, xLabel: false, xTicksVisible: true, xNumbers: false, xMinors: 0, yTicksVisible: false });
  expect(result.sequence).toEqual({ label: "n", spacing: 1, minors: 0 });
  expect(result.dynamic).toEqual({ axes: [false, false, true], ticks: [false, false, true], thickness: 2, spacing: 2, hiddenAxes: [null, null, null], hiddenTicks: [null, null, null], noNumbers: false, noNumberLabels: 0 });
  expect(result.cavalier.noNumbersTexts).toEqual(["u"]);
  expect(result.cavalier.sameTickCount).toBe(true);
  expect(result.cavalier.gridCount).toBe(0);
  expect(result.cavalier.axes).toBe(1);
  expect(result.cavalier.texts).toEqual(expect.arrayContaining(["u", "0", "2", "4"]));
  expect(result.cavalier.hiddenTexts).toEqual([]);
  expect(result.cavalier.backgroundGridSteps).toEqual([2, 3]);
});
