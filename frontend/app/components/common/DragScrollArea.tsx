"use client";

import {
  useRef,
  useState,
  type MouseEvent as ReactMouseEvent,
  type ReactNode,
} from "react";

type DragScrollAreaProps = {
  children: ReactNode;
  className?: string;
};

function shouldIgnoreDragStart(target: EventTarget | null) {
  return target instanceof HTMLElement
    ? Boolean(target.closest("button, input, select, textarea, a"))
    : false;
}

export function DragScrollArea({
  children,
  className = "ledgerTableFrame",
}: DragScrollAreaProps) {
  const containerRef = useRef<HTMLDivElement | null>(null);
  const [isDragging, setIsDragging] = useState(false);
  const dragStateRef = useRef({
    active: false,
    startX: 0,
    startY: 0,
    scrollLeft: 0,
    scrollTop: 0,
  });

  const endDrag = () => {
    dragStateRef.current.active = false;
    setIsDragging(false);
  };

  const handleMouseDown = (event: ReactMouseEvent<HTMLDivElement>) => {
    const container = containerRef.current;
    if (!container) return;
    if (shouldIgnoreDragStart(event.target)) return;

    dragStateRef.current = {
      active: true,
      startX: event.clientX,
      startY: event.clientY,
      scrollLeft: container.scrollLeft,
      scrollTop: container.scrollTop,
    };
    setIsDragging(true);
  };

  const handleMouseMove = (event: ReactMouseEvent<HTMLDivElement>) => {
    const container = containerRef.current;
    if (!container || !dragStateRef.current.active) return;

    const deltaX = event.clientX - dragStateRef.current.startX;
    const deltaY = event.clientY - dragStateRef.current.startY;

    container.scrollLeft = dragStateRef.current.scrollLeft - deltaX;
    container.scrollTop = dragStateRef.current.scrollTop - deltaY;
  };

  return (
    <div
      ref={containerRef}
      className={`${className} dragScrollArea${isDragging ? " dragging" : ""}`}
      onMouseDown={handleMouseDown}
      onMouseMove={handleMouseMove}
      onMouseUp={endDrag}
      onMouseLeave={endDrag}
    >
      {children}
    </div>
  );
}
