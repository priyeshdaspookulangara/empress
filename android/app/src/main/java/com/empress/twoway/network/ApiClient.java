package com.empress.twoway.network;

import android.os.Handler;
import android.os.Looper;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class ApiClient {

    // Default API Base URL (Change to actual server host in production, e.g. http://10.0.2.2:8000 for Android emulator)
    public static String BASE_URL = "http://10.0.2.2:8000/api/";

    private static final ExecutorService executor = Executors.newFixedThreadPool(4);
    private static final Handler mainHandler = new Handler(Looper.getMainLooper());

    public interface ApiCallback {
        void onSuccess(JSONObject response);
        void onError(String errorMessage);
    }

    public static void post(final String endpoint, final JSONObject jsonBody, final String authToken, final ApiCallback callback) {
        executor.execute(() -> {
            try {
                URL url = new URL(BASE_URL + endpoint);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json; utf-8");
                conn.setRequestProperty("Accept", "application/json");
                conn.setConnectTimeout(10000);
                conn.setReadTimeout(10000);
                conn.setDoOutput(true);

                if (authToken != null && !authToken.isEmpty()) {
                    conn.setRequestProperty("Authorization", "Bearer " + authToken);
                }

                if (jsonBody != null) {
                    try (OutputStream os = conn.getOutputStream()) {
                        byte[] input = jsonBody.toString().getBytes(StandardCharsets.UTF_8);
                        os.write(input, 0, input.length);
                    }
                }

                int responseCode = conn.getResponseCode();
                InputStream is = (responseCode >= 200 && responseCode < 400) ? conn.getInputStream() : conn.getErrorStream();

                BufferedReader reader = new BufferedReader(new InputStreamReader(is, StandardCharsets.UTF_8));
                StringBuilder responseStr = new StringBuilder();
                String line;
                while ((line = reader.readLine()) != null) {
                    responseStr.append(line.trim());
                }
                reader.close();

                final JSONObject jsonResponse = new JSONObject(responseStr.toString());

                mainHandler.post(() -> {
                    if (responseCode >= 200 && responseCode < 300) {
                        callback.onSuccess(jsonResponse);
                    } else {
                        String msg = jsonResponse.optString("message", "HTTP Error " + responseCode);
                        callback.onError(msg);
                    }
                });

            } catch (Exception e) {
                final String err = "Network request failed: " + e.getMessage();
                mainHandler.post(() -> callback.onError(err));
            }
        });
    }

    public static void get(final String endpoint, final String authToken, final ApiCallback callback) {
        executor.execute(() -> {
            try {
                URL url = new URL(BASE_URL + endpoint);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");
                conn.setRequestProperty("Accept", "application/json");
                conn.setConnectTimeout(10000);
                conn.setReadTimeout(10000);

                if (authToken != null && !authToken.isEmpty()) {
                    conn.setRequestProperty("Authorization", "Bearer " + authToken);
                }

                int responseCode = conn.getResponseCode();
                InputStream is = (responseCode >= 200 && responseCode < 400) ? conn.getInputStream() : conn.getErrorStream();

                BufferedReader reader = new BufferedReader(new InputStreamReader(is, StandardCharsets.UTF_8));
                StringBuilder responseStr = new StringBuilder();
                String line;
                while ((line = reader.readLine()) != null) {
                    responseStr.append(line.trim());
                }
                reader.close();

                final JSONObject jsonResponse = new JSONObject(responseStr.toString());

                mainHandler.post(() -> {
                    if (responseCode >= 200 && responseCode < 300) {
                        callback.onSuccess(jsonResponse);
                    } else {
                        String msg = jsonResponse.optString("message", "HTTP Error " + responseCode);
                        callback.onError(msg);
                    }
                });

            } catch (Exception e) {
                final String err = "Network request failed: " + e.getMessage();
                mainHandler.post(() -> callback.onError(err));
            }
        });
    }
}
