# Design Document: Bulk Listing Update Feature

**Project:** Aselna Marketplace Management System  
**Feature:** Multi-Channel Bulk Listing Update  
**Date:** May 4, 2026  

## 1. Overview
Currently, updating listing details (such as price or inventory) requires manual effort for each product. The **Bulk Update** feature is designed to empower administrators to perform large-scale modifications across hundreds or thousands of listings simultaneously. This feature ensures consistency across marketplaces like Walmart, Amazon, and eBay while significantly reducing manual data entry time.

## 2. User Interface: Dedicated Bulk Update Page
Instead of a simple dropdown, a new dedicated workspace will be introduced. Once listings are selected from the main manager, the user is redirected to a multi-field configuration page.

### Features:
*   **Selected Items Summary:** Displays a count and brief overview of all items pending updates.
*   **Multi-Field Action Cards:** Configure one or more fields in a single session:
    *   **Price:** 
        *   Fixed Value (e.g., set to $19.99)
        *   Relative Adjustment (e.g., +$2.00 or -$1.50)
        *   Percentage Adjustment (e.g., +10% or -5%)
    *   **Quantity:**
        *   Fixed Value (e.g., set to 100)
        *   Relative Adjustment (e.g., +50 or -20)
    *   **Titles & Descriptions:**
        *   Add Prefix/Suffix (e.g., "DEAL: " at start)
        *   Find & Replace (e.g., change "2023" to "2024")
    *   **Handling Time:** Bulk update processing days.
*   **Master Execution:** A single "Process Update" button to commit all changes.

## 3. Technical Implementation: Intelligent Feed Batching
To ensure 100% reliability and compliance with marketplace API limits, the system utilizes advanced background processing logic.

### Logic Flow:
*   **Store Grouping:** The system automatically groups selected items by their respective Store.
*   **Auto-Batching:** If the selection exceeds marketplace limits (e.g., 1,000 items per feed), the system automatically "slices" the update into multiple optimized Feed records.
*   **Asynchronous Sync:** Updates are processed via the Laravel Queue system. This prevents page timeouts and allows users to continue working while the sync runs in the background.

## 4. Safety and Traceability
*   **Audit Trail:** Every bulk change is recorded in the `Listing History` table. This allows users to verify previous values and track who made the change.
*   **Sync Monitoring:** Users can track the progress of each batch through the existing Job Status dashboard.
*   **Partial Success Handling:** If a marketplace rejects a specific item (e.g., due to a price floor violation), the system logs the error for that item while ensuring the rest of the batch is updated successfully.

## 5. Implementation Workflow
1.  **Select:** Use the main Listing Manager to filter and select products.
2.  **Configure:** Navigate to the Bulk Update page and enter the desired changes.
3.  **Execute:** Hit "Apply Changes."
4.  **Sync:** The system handles database updates and marketplace communication in optimized batches.
5.  **Monitor:** Review the sync status via the dashboard.
