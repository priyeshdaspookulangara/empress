package com.empress.twoway.ui;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.empress.twoway.R;
import com.empress.twoway.adapters.TransactionAdapter;
import com.empress.twoway.models.Transaction;
import com.empress.twoway.network.ApiClient;
import com.empress.twoway.util.SessionManager;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.List;
import java.util.Locale;

public class DashboardFragment extends Fragment {

    private SwipeRefreshLayout swipeRefresh;
    private TextView tvWelcomeName, tvPackageBadge, tvKycBadge;
    private TextView tvTotalBalance, tvUserWallet, tvBurfeeWallet, tvCharityWallet;
    private TextView tvDownlineCount, tvRebirthCount;
    private RecyclerView rvRecentTransactions;

    private SessionManager sessionManager;
    private final List<Transaction> transactionList = new ArrayList<>();
    private TransactionAdapter adapter;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_dashboard, container, false);

        sessionManager = new SessionManager(requireContext());

        swipeRefresh = view.findViewById(R.id.swipeRefresh);
        tvWelcomeName = view.findViewById(R.id.tvWelcomeName);
        tvPackageBadge = view.findViewById(R.id.tvPackageBadge);
        tvKycBadge = view.findViewById(R.id.tvKycBadge);
        tvTotalBalance = view.findViewById(R.id.tvTotalBalance);
        tvUserWallet = view.findViewById(R.id.tvUserWallet);
        tvBurfeeWallet = view.findViewById(R.id.tvBurfeeWallet);
        tvCharityWallet = view.findViewById(R.id.tvCharityWallet);
        tvDownlineCount = view.findViewById(R.id.tvDownlineCount);
        tvRebirthCount = view.findViewById(R.id.tvRebirthCount);
        rvRecentTransactions = view.findViewById(R.id.rvRecentTransactions);

        rvRecentTransactions.setLayoutManager(new LinearLayoutManager(requireContext()));
        adapter = new TransactionAdapter(transactionList);
        rvRecentTransactions.setAdapter(adapter);

        swipeRefresh.setOnRefreshListener(this::loadDashboardData);

        loadDashboardData();

        return view;
    }

    private void loadDashboardData() {
        swipeRefresh.setRefreshing(true);

        ApiClient.get("user/dashboard.php", sessionManager.getToken(), new ApiClient.ApiCallback() {
            @Override
            public void onSuccess(JSONObject response) {
                if (!isAdded()) return;
                swipeRefresh.setRefreshing(false);

                if (response.optBoolean("success")) {
                    JSONObject data = response.optJSONObject("data");
                    if (data != null) {
                        // Profile
                        JSONObject profile = data.optJSONObject("profile");
                        if (profile != null) {
                            tvWelcomeName.setText("Welcome, " + profile.optString("name", sessionManager.getName()));
                            tvPackageBadge.setText("Package: " + profile.optString("package_type", "Royal Starter"));
                            tvKycBadge.setText("KYC: " + profile.optString("kyc_status", "Pending"));
                        }

                        // Wallet
                        JSONObject wallet = data.optJSONObject("wallet");
                        if (wallet != null) {
                            tvTotalBalance.setText(String.format(Locale.US, "$%.2f", wallet.optDouble("balance", 0.0)));
                            tvUserWallet.setText(String.format(Locale.US, "$%.2f", wallet.optDouble("user_wallet_50", 0.0)));
                            tvBurfeeWallet.setText(String.format(Locale.US, "$%.2f", wallet.optDouble("burfee_cart_wallet", 0.0)));
                            tvCharityWallet.setText(String.format(Locale.US, "$%.2f", wallet.optDouble("charity_wallet", 0.0)));
                        }

                        // Network
                        JSONObject network = data.optJSONObject("network");
                        if (network != null) {
                            tvDownlineCount.setText(String.valueOf(network.optInt("total_downline_count", 0)));
                            tvRebirthCount.setText(String.valueOf(network.optInt("total_rebirths_earned", 0)));
                        }

                        // Recent Transactions
                        JSONArray txArray = data.optJSONArray("recent_transactions");
                        transactionList.clear();
                        if (txArray != null) {
                            for (int i = 0; i < txArray.length(); i++) {
                                JSONObject txObj = txArray.optJSONObject(i);
                                if (txObj != null) {
                                    transactionList.add(new Transaction(
                                            txObj.optString("type"),
                                            txObj.optDouble("amount"),
                                            txObj.optString("status"),
                                            txObj.optString("description"),
                                            txObj.optString("created_at")
                                    ));
                                }
                            }
                        }
                        adapter.notifyDataSetChanged();
                    }
                } else {
                    Toast.makeText(requireContext(), response.optString("message", "Failed to load dashboard"), Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onError(String errorMessage) {
                if (!isAdded()) return;
                swipeRefresh.setRefreshing(false);
                Toast.makeText(requireContext(), errorMessage, Toast.LENGTH_SHORT).show();
            }
        });
    }
}
