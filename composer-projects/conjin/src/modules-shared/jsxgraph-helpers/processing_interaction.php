<?
    namespace Conjin\ModulesShared\JSXGraphHelpers;


    ////////////////////////////////
    // Plot interaction behaviors //
    ////////////////////////////////

    // Enabled zoom and pan are always available through visible navigation controls.
    enum Zoom {
        case Disabled;
        case EnabledWithNav;
    }

    enum Pan {
        case Disabled;
        case EnabledWithNav;
    }

    // Rotation is only part of option types for interactive 3D views and is always available through visible sliders when enabled.
    enum Rotation {
        case Disabled;
        case EnabledWithSliders;
    }

    // Allow cases grant an additional input method only while the corresponding feature is enabled. They intentionally remain dormant otherwise.
    enum WheelBehavior {
        case Disabled;
        case AllowForZoom;
    }

    enum PinchBehavior {
        case Disabled;
        case AllowPinchForZoom;
    }

    enum TwoFingerTranslationBehavior {
        case Disabled;
        case AllowForPan;
    }

    // Drag behaviors are split by plot dimensionality so rotation cannot be selected for a 2D interaction surface.
    enum OneFingerDragBehavior2D {
        case Disabled;
        case AllowForPan;
    }

    enum OneFingerDragBehavior3D {
        case Disabled;
        case AllowForPan;
        case AllowForRotation;
    }

    enum MouseDragBehavior2D {
        case Disabled;
        case AllowForPan;
    }

    enum MouseDragBehavior3D {
        case Disabled;
        case AllowForPan;
        case AllowForRotation;
    }


    //////////////////////////////////
    // Resolved interaction options //
    //////////////////////////////////

    readonly class ResolvedPlotInteractions {
        public function __construct(
            public bool $show_navigation,
            public bool $show_zoom,
            public bool $pan_enabled,
            public bool $pan_need_two_fingers,
            public bool $zoom_enabled,
            public bool $zoom_wheel,
            public bool $zoom_pinch,
            public bool $rotation_enabled = false,
            public bool $rotation_drag_enabled = false,
        ) {}
    }


    /////////////////////////////
    // Interaction resolution //
    /////////////////////////////

    function jsx_resolve_2d_plot_interactions(
        string $plot_name,
        Zoom $zoom,
        Pan $pan,
        WheelBehavior $wheel_behavior,
        PinchBehavior $pinch_behavior,
        TwoFingerTranslationBehavior $two_finger_translation_behavior,
        OneFingerDragBehavior2D $one_finger_drag_behavior,
        MouseDragBehavior2D $mouse_drag_behavior,
    ): ResolvedPlotInteractions {
        return jsx_resolve_plot_interactions(
            plot_name: $plot_name,
            zoom: $zoom,
            pan: $pan,
            wheel_behavior: $wheel_behavior,
            pinch_behavior: $pinch_behavior,
            two_finger_translation_behavior: $two_finger_translation_behavior,
            allow_one_finger_pan: $one_finger_drag_behavior === OneFingerDragBehavior2D::AllowForPan,
            allow_mouse_drag_pan: $mouse_drag_behavior === MouseDragBehavior2D::AllowForPan,
        );
    }

    function jsx_resolve_3d_plot_interactions(
        string $plot_name,
        Zoom $zoom,
        Pan $pan,
        Rotation $rotation,
        WheelBehavior $wheel_behavior,
        PinchBehavior $pinch_behavior,
        TwoFingerTranslationBehavior $two_finger_translation_behavior,
        OneFingerDragBehavior3D $one_finger_drag_behavior,
        MouseDragBehavior3D $mouse_drag_behavior,
    ): ResolvedPlotInteractions {
        $resolved = jsx_resolve_plot_interactions(
            plot_name: $plot_name,
            zoom: $zoom,
            pan: $pan,
            wheel_behavior: $wheel_behavior,
            pinch_behavior: $pinch_behavior,
            two_finger_translation_behavior: $two_finger_translation_behavior,
            allow_one_finger_pan: $one_finger_drag_behavior === OneFingerDragBehavior3D::AllowForPan,
            allow_mouse_drag_pan: $mouse_drag_behavior === MouseDragBehavior3D::AllowForPan,
        );
        $rotation_enabled = $rotation === Rotation::EnabledWithSliders;
        $allow_one_finger_rotation = $rotation_enabled && $one_finger_drag_behavior === OneFingerDragBehavior3D::AllowForRotation;
        $allow_mouse_drag_rotation = $rotation_enabled && $mouse_drag_behavior === MouseDragBehavior3D::AllowForRotation;

        if ($allow_one_finger_rotation !== $allow_mouse_drag_rotation) {
            throw new \InvalidArgumentException(
                $plot_name . ' cannot configure mouse-drag and one-finger rotation independently because JSXGraph uses one pointer-rotation setting for both'
            );
        }

        if ($resolved->pan_enabled && $allow_one_finger_rotation) {
            throw new \InvalidArgumentException($plot_name . ' cannot enable direct panning and drag rotation at the same time');
        }

        return new ResolvedPlotInteractions(
            show_navigation: $resolved->show_navigation,
            show_zoom: $resolved->show_zoom,
            pan_enabled: $resolved->pan_enabled,
            pan_need_two_fingers: $resolved->pan_need_two_fingers,
            zoom_enabled: $resolved->zoom_enabled,
            zoom_wheel: $resolved->zoom_wheel,
            zoom_pinch: $resolved->zoom_pinch,
            rotation_enabled: $rotation_enabled,
            rotation_drag_enabled: $allow_one_finger_rotation,
        );
    }

    function jsx_resolve_plot_interactions(
        string $plot_name,
        Zoom $zoom,
        Pan $pan,
        WheelBehavior $wheel_behavior,
        PinchBehavior $pinch_behavior,
        TwoFingerTranslationBehavior $two_finger_translation_behavior,
        bool $allow_one_finger_pan,
        bool $allow_mouse_drag_pan,
    ): ResolvedPlotInteractions {
        $zoom_enabled = $zoom === Zoom::EnabledWithNav;
        $pan_enabled = $pan === Pan::EnabledWithNav;

        if ($zoom_enabled && !$pan_enabled) {
            throw new \InvalidArgumentException(
                $plot_name . ' cannot enable zoom navigation while pan is disabled because the JSXGraph navigation bar always includes working pan controls'
            );
        }

        $allow_one_finger_pan = $pan_enabled && $allow_one_finger_pan;
        $allow_two_finger_pan = $pan_enabled && $two_finger_translation_behavior === TwoFingerTranslationBehavior::AllowForPan;
        $allow_mouse_drag_pan = $pan_enabled && $allow_mouse_drag_pan;

        if ($allow_one_finger_pan && $allow_two_finger_pan) {
            throw new \InvalidArgumentException(
                $plot_name . ' cannot enable both one-finger and two-finger panning because JSXGraph requires one touch mode'
            );
        }

        if ($allow_mouse_drag_pan !== ($allow_one_finger_pan || $allow_two_finger_pan)) {
            throw new \InvalidArgumentException(
                $plot_name . ' cannot configure mouse-drag and touch panning independently because JSXGraph uses one pointer-pan setting for both'
            );
        }

        $direct_pan_enabled = $allow_mouse_drag_pan;
        $wheel_zoom_enabled = $zoom_enabled && $wheel_behavior === WheelBehavior::AllowForZoom;
        $pinch_zoom_enabled = $zoom_enabled && $pinch_behavior === PinchBehavior::AllowPinchForZoom;

        return new ResolvedPlotInteractions(
            show_navigation: $zoom_enabled || $pan_enabled,
            show_zoom: $zoom_enabled,
            pan_enabled: $direct_pan_enabled,
            pan_need_two_fingers: $allow_two_finger_pan,
            zoom_enabled: $wheel_zoom_enabled || $pinch_zoom_enabled,
            zoom_wheel: $wheel_zoom_enabled,
            zoom_pinch: $pinch_zoom_enabled,
        );
    }
?>
