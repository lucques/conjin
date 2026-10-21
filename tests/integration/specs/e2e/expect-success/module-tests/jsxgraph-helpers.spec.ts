import { expect, test } from "../../../../fixtures/page-health";

test.describe("jsxgraph-helpers", () => {
  test("loads a board and the helper-generated initialization without errors", async ({ healthyPage }) => {
    const { page } = healthyPage;
    const response = await page.goto("/module-tests-expect-success/jsxgraph-helpers/");

    expect(response?.ok()).toBe(true);
    await expect(page.locator(".jsxgraph-plot")).toHaveCount(4);
    await expect(page.locator("#jsxgraph-status")).toHaveAttribute("data-ready", "true");

    const jsxGraphIsAvailable = await page.evaluate(() => {
      const jsxGraph = (window as unknown as {
        JXG?: { JSXGraph?: { initBoard?: unknown } };
      }).JXG;
      return typeof jsxGraph?.JSXGraph?.initBoard === "function";
    });
    expect(jsxGraphIsAvailable).toBe(true);

    const interactions = await page.evaluate(() => {
      const jxg = (window as unknown as {
        JXG?: {
          boards?: Record<string, {
            attr: {
              shownavigation: boolean;
              showzoom: boolean;
              pan: { enabled: boolean; needtwofingers: boolean };
              zoom: { enabled: boolean; wheel: boolean; pinch: boolean };
            };
            defaultAxes: {
              x: {
                evalVisProp: (name: string) => unknown;
                label: { evalVisProp: (name: string) => unknown };
                defaultTicks: { evalVisProp: (name: string) => unknown };
              };
              y: {
                evalVisProp: (name: string) => unknown;
                defaultTicks: { evalVisProp: (name: string) => unknown };
              };
            };
            grids: Array<{ evalVisProp: (name: string) => unknown }>;
            objectsList: Array<{
              elType: string;
              evalVisProp: (name: string) => unknown;
            }>;
          }>;
        };
      }).JXG;
      const boards = Object.values(jxg?.boards ?? {});
      const board = boards[0];
      const sequenceBoard = boards[1];
      const majorTicks = (testBoard: typeof board) => testBoard?.objectsList
        .filter((object) => object.elType === "ticks" && object.evalVisProp("drawlabels") === false);

      return {
        showNavigation: board?.attr.shownavigation,
        showZoom: board?.attr.showzoom,
        panEnabled: board?.attr.pan.enabled,
        panNeedsTwoFingers: board?.attr.pan.needtwofingers,
        zoomEnabled: board?.attr.zoom.enabled,
        wheelZoom: board?.attr.zoom.wheel,
        pinchZoom: board?.attr.zoom.pinch,
        axisLabelFontSize: board?.defaultAxes.x.label.evalVisProp("fontsize"),
        axisNumbersFontSize: board?.defaultAxes.x.defaultTicks.evalVisProp("label.fontsize"),
        axisMajorTickHeight: board?.defaultAxes.x.defaultTicks.evalVisProp("majorheight"),
        xAxisNumbersOffset: board?.defaultAxes.x.defaultTicks.evalVisProp("label.offset"),
        yAxisNumbersOffset: board?.defaultAxes.y.defaultTicks.evalVisProp("label.offset"),
        printAxisStrokeColor: board?.defaultAxes.x.evalVisProp("strokecolor"),
        printAxisStrokeWidths: [
          board?.defaultAxes.x.evalVisProp("strokewidth"),
          board?.defaultAxes.y.evalVisProp("strokewidth"),
        ],
        printMajorTickStrokeWidths: majorTicks(board)?.map((ticks) => ticks.evalVisProp("strokewidth")),
        printMajorTickHeights: majorTicks(board)?.map((ticks) => ticks.evalVisProp("majorheight")),
        printGridStrokeColor: board?.grids[0]?.evalVisProp("strokecolor"),
        sequencePrintAxisStrokeWidths: [
          sequenceBoard?.defaultAxes.x.evalVisProp("strokewidth"),
          sequenceBoard?.defaultAxes.y.evalVisProp("strokewidth"),
        ],
        sequenceMajorTickHeight: sequenceBoard?.defaultAxes.x.defaultTicks.evalVisProp("majorheight"),
        sequencePrintMajorTickStrokeWidths: majorTicks(sequenceBoard)?.map((ticks) => ticks.evalVisProp("strokewidth")),
        sequencePrintMajorTickHeights: majorTicks(sequenceBoard)?.map((ticks) => ticks.evalVisProp("majorheight")),
        sequencePrintGridStrokeColor: sequenceBoard?.grids[0]?.evalVisProp("strokecolor"),
      };
    });

    expect(interactions).toEqual({
      showNavigation: true,
      showZoom: true,
      panEnabled: true,
      panNeedsTwoFingers: false,
      zoomEnabled: true,
      wheelZoom: true,
      pinchZoom: true,
      axisLabelFontSize: 18,
      axisNumbersFontSize: 10,
      axisMajorTickHeight: 13,
      xAxisNumbersOffset: [0, -11],
      yAxisNumbersOffset: [-12, 0],
      printAxisStrokeColor: "black",
      printAxisStrokeWidths: [3, 3],
      printMajorTickStrokeWidths: [3, 3],
      printMajorTickHeights: [13, 13],
      printGridStrokeColor: "black",
      sequencePrintAxisStrokeWidths: [1.5, 1.5],
      sequenceMajorTickHeight: 11.5,
      sequencePrintMajorTickStrokeWidths: [1.5, 1.5],
      sequencePrintMajorTickHeights: [11.5, 11.5],
      sequencePrintGridStrokeColor: "black",
    });

    const viewportBehavior = await page.evaluate(() => {
      type TestCurve = {
        minX: () => number;
        maxX: () => number;
        label: { evalVisProp: (name: string) => unknown };
      };
      type TestPoint = {
        X: () => number;
        elType: string;
        visProp: { face: string };
      };
      type TestBoard = {
        objectsList: TestPoint[];
        setBoundingBox: (bounds: [number, number, number, number], keepAspectRatio: boolean) => void;
      };
      const testWindow = window as unknown as {
        jsxGraphFunctionBoard: TestBoard;
        jsxGraphFunction: TestCurve;
        jsxGraphRestrictedFunction: TestCurve;
        jsxGraphSequenceBoard: TestBoard;
      };

      testWindow.jsxGraphFunctionBoard.setBoundingBox([-8, 8, 8, -8], false);
      testWindow.jsxGraphSequenceBoard.setBoundingBox([5.5, 10, 8.5, 0], false);

      return {
        functionDomain: [testWindow.jsxGraphFunction.minX(), testWindow.jsxGraphFunction.maxX()],
        functionLabelFontSize: testWindow.jsxGraphFunction.label.evalVisProp("fontsize"),
        restrictedFunctionDomain: [
          testWindow.jsxGraphRestrictedFunction.minX(),
          testWindow.jsxGraphRestrictedFunction.maxX(),
        ],
        visibleSequenceIndices: [...new Set(
          testWindow.jsxGraphSequenceBoard.objectsList
            .filter((object) => object.elType === "point" && object.visProp.face === "x")
            .map((point) => point.X()),
        )].sort((a, b) => a - b),
      };
    });

    expect(viewportBehavior).toEqual({
      functionDomain: [-8, 8],
      functionLabelFontSize: 14,
      restrictedFunctionDomain: [-2, 2],
      visibleSequenceIndices: [6, 7, 8],
    });

    const configured3DView = await page.evaluate(() => {
      type TestSlider = {
        Value: () => number;
        point1: { X: () => number; Y: () => number };
        point2: { X: () => number; Y: () => number };
      };
      type TestView3D = {
        az_slide: TestSlider;
        el_slide: TestSlider;
        project3DTo2D: (coords: [number, number, number]) => [number, number, number, number];
      };
      const view = (window as unknown as { jsxGraph3DView: TestView3D }).jsxGraph3DView;
      const axes = (window as unknown as {
        jsxGraph3DAxes: Array<{ evalVisProp: (name: string) => unknown }>;
      }).jsxGraph3DAxes;
      const ticks = (window as unknown as {
        jsxGraph3DTicks: Array<{ evalVisProp: (name: string) => unknown }>;
      }).jsxGraph3DTicks;
      const projectedCorners = [-4, 4].flatMap((x) =>
        [-2, 2].flatMap((y) =>
          [-3, 3].map((z) => view.project3DTo2D([x, y, z])),
        ),
      );
      const projectedX = projectedCorners.map((point) => point[1]);
      const projectedY = projectedCorners.map((point) => point[2]);
      const plot = document.querySelectorAll(".jsxgraph-plot")[2];
      const visibleOriginLabels = [...plot.querySelectorAll("text, div")].filter((element) => {
        const style = getComputedStyle(element);
        return element.children.length === 0
          && element.textContent?.trim() === "0"
          && style.display !== "none"
          && style.visibility === "visible";
      }).length;

      return {
        azimuth: view.az_slide.Value(),
        elevation: view.el_slide.Value(),
        printAxisStrokeWidths: axes.map((axis) => axis.evalVisProp("strokewidth")),
        printMajorTickStrokeWidths: ticks.map((tick) => tick.evalVisProp("strokewidth")),
        printMajorTickHeights: ticks.map((tick) => tick.evalVisProp("majorheight")),
        visibleOriginLabels,
        azimuthSlider: {
          xMin: view.az_slide.point1.X(),
          xMax: view.az_slide.point2.X(),
        },
        elevationSlider: {
          yMin: view.el_slide.point1.Y(),
          yMax: view.el_slide.point2.Y(),
        },
        projectedBounds: {
          xMin: Math.min(...projectedX),
          xMax: Math.max(...projectedX),
          yMin: Math.min(...projectedY),
          yMax: Math.max(...projectedY),
        },
      };
    });

    expect(configured3DView.azimuth).toBeCloseTo(5 * Math.PI / 6);
    expect(configured3DView.elevation).toBeCloseTo(-Math.PI / 8);
    expect(configured3DView.printAxisStrokeWidths).toEqual([2.5, 2.5, 2.5]);
    expect(configured3DView.printMajorTickStrokeWidths).toEqual([2.5, 2.5, 2.5]);
    configured3DView.printMajorTickHeights.forEach((height) => {
      expect(height).toBeCloseTo(0.2163550133);
    });
    expect(configured3DView.visibleOriginLabels).toBe(1);
    expect(configured3DView.projectedBounds.xMin).toBeGreaterThanOrEqual(50);
    expect(configured3DView.projectedBounds.xMax).toBeLessThanOrEqual(480);
    expect(configured3DView.projectedBounds.yMin).toBeCloseTo(50);
    expect(configured3DView.projectedBounds.yMax).toBeCloseTo(380);
    expect(configured3DView.azimuthSlider.xMax - configured3DView.azimuthSlider.xMin).toBeCloseTo(250);
    expect((configured3DView.azimuthSlider.xMin + configured3DView.azimuthSlider.xMax) / 2).toBeCloseTo(250);
    expect(configured3DView.elevationSlider.yMax - configured3DView.elevationSlider.yMin).toBeCloseTo(250);
    expect((configured3DView.elevationSlider.yMin + configured3DView.elevationSlider.yMax) / 2).toBeCloseTo(200);

    const cavalierAppearance = await page.evaluate(() => {
      type TestBoard = {
        unitX: number;
        grids: Array<{ evalVisProp: (name: string) => unknown }>;
        objectsList: Array<{
          elType: string;
          evalVisProp: (name: string) => unknown;
          point1: { coords: { scrCoords: number[] } };
          point2: { coords: { scrCoords: number[] } };
        }>;
      };
      type LabeledElement = {
        label: { evalVisProp: (name: string) => unknown };
      };
      const testWindow = window as unknown as {
        jsxGraphCavalierBoard: TestBoard;
        jsxGraphCavalierPoint: LabeledElement;
        jsxGraphCavalierLine: LabeledElement;
      };
      const board = testWindow.jsxGraphCavalierBoard;
      const majorTicks = board.objectsList.filter((object) => object.elType === "line"
        && object.evalVisProp("lastarrow") === false
        && object.evalVisProp("strokecolor") === "black");
      const plot = document.querySelectorAll(".jsxgraph-plot")[3];
      return {
        pointLabelFontSize: testWindow.jsxGraphCavalierPoint.label.evalVisProp("fontsize"),
        lineLabelFontSize: testWindow.jsxGraphCavalierLine.label.evalVisProp("fontsize"),
        originLabels: [...plot.querySelectorAll("text, div")].filter((element) => {
          const style = getComputedStyle(element);
          return element.children.length === 0
            && element.textContent?.trim() === "0"
            && style.display !== "none"
            && style.visibility === "visible";
        }).length,
        printAxisStrokeWidths: board.objectsList
          .filter((object) => object.elType === "line" && object.evalVisProp("lastarrow") === true)
          .map((axis) => axis.evalVisProp("strokewidth")),
        printMajorTickStrokeWidths: [...new Set(majorTicks
          .map((tick) => tick.evalVisProp("strokewidth")))],
        printMajorTickLengths: majorTicks.map((tick) => Math.hypot(
          tick.point2.coords.scrCoords[1] - tick.point1.coords.scrCoords[1],
          tick.point2.coords.scrCoords[2] - tick.point1.coords.scrCoords[2],
        )),
        expectedMajorTickLength: 0.4 * board.unitX + 5,
        printGridStrokeColor: board.grids[0]?.evalVisProp("strokecolor"),
      };
    });

    expect(cavalierAppearance.printMajorTickLengths.length).toBeGreaterThan(0);
    cavalierAppearance.printMajorTickLengths.forEach((length) => {
      expect(length).toBeCloseTo(cavalierAppearance.expectedMajorTickLength);
    });
    expect(cavalierAppearance).toMatchObject({
      pointLabelFontSize: 16,
      lineLabelFontSize: 12,
      originLabels: 1,
      printAxisStrokeWidths: [5, 5, 5],
      printMajorTickStrokeWidths: [5],
      printGridStrokeColor: "black",
    });

    const reset3DView = await page.evaluate(() => {
      type TestView3D = {
        az_slide: { Value: () => number };
        el_slide: { Value: () => number };
        setView: (azimuth: number, elevation: number) => void;
      };
      const view = (window as unknown as { jsxGraph3DView: TestView3D }).jsxGraph3DView;
      view.setView(Math.PI / 4, Math.PI / 4);
      const plot = document.querySelectorAll(".jsxgraph-plot")[2];
      const resetButton = plot.parentElement?.querySelector<HTMLButtonElement>("button[aria-label='Ansicht zurücksetzen']");
      resetButton?.click();

      return {
        azimuth: view.az_slide.Value(),
        elevation: view.el_slide.Value(),
      };
    });

    expect(reset3DView.azimuth).toBeCloseTo(5 * Math.PI / 6);
    expect(reset3DView.elevation).toBeCloseTo(-Math.PI / 8);
  });
});
