package com.model;

import java.io.IOException;
import java.io.PrintWriter;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.List;

import jakarta.servlet.RequestDispatcher;
import jakarta.servlet.ServletException;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;

@WebServlet("/UserServlet")
public class UserServlet extends HttpServlet {

    private static final String DB_URL      = "jdbc:mysql://localhost:3306/students";
    private static final String DB_USER     = "root";
    private static final String DB_PASSWORD = "752005@khan";

    @Override
    protected void doGet(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {

        // ── Debug output written directly to the response ──────────────────────
        // Remove (or comment out) the debug block once everything works.
        StringBuilder debug = new StringBuilder();
        List<String[]> users = new ArrayList<>();

        try {
            // 1. Load the JDBC driver
            debug.append("[DEBUG] Loading JDBC driver...<br>");
            Class.forName("com.mysql.cj.jdbc.Driver");
            debug.append("[DEBUG] Driver loaded OK.<br>");

            // 2. Open connection
            debug.append("[DEBUG] Connecting to: ").append(DB_URL).append("<br>");
            Connection conn = DriverManager.getConnection(DB_URL, DB_USER, DB_PASSWORD);
            debug.append("[DEBUG] Connection established.<br>");

            // 3. Run query
            String sql = "SELECT id, name, age FROM student";
            debug.append("[DEBUG] Executing query: ").append(sql).append("<br>");
            Statement stmt = conn.createStatement();
            ResultSet rs   = stmt.executeQuery(sql);

            // 4. Collect rows
            int rowCount = 0;
            while (rs.next()) {
                rowCount++;
                String id   = String.valueOf(rs.getInt("id"));
                String name = rs.getString("name");
                String age  = String.valueOf(rs.getInt("age"));
                users.add(new String[]{id, name, age});
                debug.append("[DEBUG] Row ").append(rowCount)
                     .append(": id=").append(id)
                     .append(", name=").append(name)
                     .append(", age=").append(age)
                     .append("<br>");
            }

            debug.append("[DEBUG] Total rows fetched: ").append(rowCount).append("<br>");

            rs.close();
            stmt.close();
            conn.close();

        } catch (ClassNotFoundException e) {
            // JDBC driver JAR is missing from the classpath / WEB-INF/lib
            debug.append("<b style='color:red'>[ERROR] JDBC Driver not found: ")
                 .append(e.getMessage())
                 .append("</b><br>")
                 .append("Fix: Add mysql-connector-j-*.jar to WEB-INF/lib.<br>");
            showDebugPage(response, debug.toString(), users);
            return;

        } catch (java.sql.SQLException e) {
            // Could be wrong URL, wrong credentials, table doesn't exist, etc.
            debug.append("<b style='color:red'>[ERROR] SQL Exception: ")
                 .append(e.getMessage())
                 .append("</b><br>")
                 .append("SQLState: ").append(e.getSQLState()).append("<br>")
                 .append("ErrorCode: ").append(e.getErrorCode()).append("<br>");

            // Common causes:
            if (e.getSQLState() != null && e.getSQLState().startsWith("28")) {
                debug.append("Hint: Access denied — check DB_USER / DB_PASSWORD.<br>");
            } else if (e.getSQLState() != null && e.getSQLState().equals("42S02")) {
                debug.append("Hint: Table 'student' does not exist in database 'students'.<br>");
            } else if (e.getMessage() != null && e.getMessage().contains("Communications link failure")) {
                debug.append("Hint: MySQL is not running or the port/host is wrong.<br>");
            } else if (e.getMessage() != null && e.getMessage().contains("Unknown database")) {
                debug.append("Hint: Database 'students' does not exist — create it first.<br>");
            }

            showDebugPage(response, debug.toString(), users);
            return;
        }

        // ── Everything worked — pass data to user.jsp ───────────────────────────
        request.setAttribute("users", users);
        request.setAttribute("debugInfo", debug.toString()); // optional: show in JSP during dev

        RequestDispatcher rd = request.getRequestDispatcher("user.jsp");
        rd.forward(request, response);
    }

    /**
     * Renders a plain HTML debug page directly in the browser.
     * Called only when an exception prevents forwarding to user.jsp.
     */
    private void showDebugPage(HttpServletResponse response,
                               String debugHtml,
                               List<String[]> users) throws IOException {
        response.setContentType("text/html;charset=UTF-8");
        PrintWriter out = response.getWriter();
        out.println("<!DOCTYPE html><html><head><title>UserServlet Debug</title></head><body>");
        out.println("<h2>UserServlet — Debug Output</h2>");
        out.println("<div style='font-family:monospace;background:#f4f4f4;padding:12px;border:1px solid #ccc'>");
        out.println(debugHtml);
        out.println("</div>");

        if (!users.isEmpty()) {
            out.println("<h3>Rows collected before error:</h3><table border='1' cellpadding='6'>");
            out.println("<tr><th>ID</th><th>Name</th><th>Age</th></tr>");
            for (String[] row : users) {
                out.println("<tr><td>" + row[0] + "</td><td>" + row[1] + "</td><td>" + row[2] + "</td></tr>");
            }
            out.println("</table>");
        }

        out.println("</body></html>");
    }
}
