type PaginationControlsProps = {
  currentPage: number;
  pageSize: number;
  totalItems: number;
  onPageChange: (page: number) => void;
};

function buildPageNumbers(currentPage: number, totalPages: number) {
  const pages = new Set<number>([1, totalPages, currentPage]);

  for (let page = currentPage - 1; page <= currentPage + 1; page += 1) {
    if (page > 1 && page < totalPages) pages.add(page);
  }

  return Array.from(pages).sort((left, right) => left - right);
}

export function PaginationControls({
  currentPage,
  pageSize,
  totalItems,
  onPageChange,
}: PaginationControlsProps) {
  const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
  const startItem = totalItems === 0 ? 0 : (currentPage - 1) * pageSize + 1;
  const endItem = Math.min(currentPage * pageSize, totalItems);
  const pageNumbers = buildPageNumbers(currentPage, totalPages);

  return (
    <div className="paginationBar">
      <div className="paginationSummary">
        {totalItems === 0
          ? "0件"
          : `${startItem}-${endItem}件 / ${totalItems}件`}
      </div>

      <div className="paginationControls" role="navigation" aria-label="ページ送り">
        <button
          type="button"
          onClick={() => onPageChange(currentPage - 1)}
          disabled={currentPage <= 1}
        >
          前へ
        </button>

        {pageNumbers.map((pageNumber, index) => {
          const previousPage = pageNumbers[index - 1];
          const showGap = previousPage && pageNumber - previousPage > 1;

          return (
            <span key={pageNumber} className="paginationPageGroup">
              {showGap ? <span className="paginationEllipsis">…</span> : null}
              <button
                type="button"
                className={pageNumber === currentPage ? "active" : ""}
                onClick={() => onPageChange(pageNumber)}
                aria-current={pageNumber === currentPage ? "page" : undefined}
              >
                {pageNumber}
              </button>
            </span>
          );
        })}

        <button
          type="button"
          onClick={() => onPageChange(currentPage + 1)}
          disabled={currentPage >= totalPages}
        >
          次へ
        </button>
      </div>
    </div>
  );
}
