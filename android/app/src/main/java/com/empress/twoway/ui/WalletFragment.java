package com.empress.twoway.ui;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
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
import com.empress.twoway.adapters.WithdrawalAdapter;
import com.empress.twoway.models.Transaction;
import com.empress.twoway.models.Withdrawal;
import com.empress.twoway.network.ApiClient;
import com.empress.twoway.util.SessionManager;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.List;
import java.util.Locale;

public class WalletFragment extends Fragment {

    private SwipeRefreshLayout swipeRefresh;
    private TextView tvUserWalletBalance, tvKycStatusMessage;
    private EditText etWithdrawAmount;
    private Button btnSubmitWithdrawal;
    private ProgressBar pbWithdraw;
    private RecyclerView rvWithdrawals, rvTransactions;

    private SessionManager sessionManager;
    private final List<Withdrawal> withdrawalList = new ArrayList<>();
    private final List<Transaction> transactionList = new ArrayList<>();
    private WithdrawalAdapter withdrawalAdapter;
    private TransactionAdapter transactionAdapter;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_wallet, container, false);

        sessionManager = new SessionManager(requireContext());

        swipeRefresh = view.findViewById(R.id.swipeRefresh);
        tvUserWalletBalance = view.findViewById(R.id.tvUserWalletBalance);
        tvKycStatusMessage = view.findViewById(R.id.tvKycStatusMessage);
        etWithdrawAmount = view.findViewById(R.id.etWithdrawAmount);
        btnSubmitWithdrawal = view.findViewById(R.id.btnSubmitWithdrawal);
        pbWithdraw = view.findViewById(R.id.pbWithdraw);
        rvWithdrawals = view.findViewById(R.id.rvWithdrawals);
        rvTransactions = view.findViewById(R.id.rvTransactions);

        rvWithdrawals.setLayoutManager(new LinearLayoutManager(requireContext()));
        withdrawalAdapter = new WithdrawalAdapter(withdrawalList);
        rvWithdrawals.setAdapter(withdrawalAdapter);

        rvTransactions.setLayoutManager(new LinearLayoutManager(requireContext()));
        transactionAdapter = new TransactionAdapter(transactionList);
        rvTransactions.setAdapter(transactionAdapter);

        swipeRefresh.setOnRefreshListener(this::loadWalletData);

        btnSubmitWithdrawal.setOnClickListener(v -> submitWithdrawalRequest());

        loadWalletData();

        return view;
    }

    private void loadWalletData() {
        swipeRefresh.setRefreshing(true);

        ApiClient.get("user/wallet.php", sessionManager.getToken(), new ApiClient.ApiCallback() {
            @Override
            public void onSuccess(JSONObject response) {
                if (!isAdded()) return;
                swipeRefresh.setRefreshing(false);

                if (response.optBoolean("success")) {
                    JSONObject data = response.optJSONObject("data");
                    if (data != null) {
                        // Wallet
                        JSONObject wallet = data.optJSONObject("wallet");
                        if (wallet != null) {
                            tvUserWalletBalance.setText(String.format(Locale.US, "$%.2f USD", wallet.optDouble("user_wallet_50", 0.0)));
                        }

                        // Withdrawals
                        JSONArray wArray = data.optJSONArray("withdrawals");
                        withdrawalList.clear();
                        if (wArray != null) {
                            for (int i = 0; i < wArray.length(); i++) {
                                JSONObject wObj = wArray.optJSONObject(i);
                                if (wObj != null) {
                                    withdrawalList.add(new Withdrawal(
                                            wObj.optInt("id"),
                                            wObj.optDouble("amount"),
                                            wObj.optString("status"),
                                            wObj.optString("request_date")
                                    ));
                                }
                            }
                        }
                        withdrawalAdapter.notifyDataSetChanged();

                        // Transactions
                        JSONArray txArray = data.optJSONArray("transactions");
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
                        transactionAdapter.notifyDataSetChanged();
                    }
                } else {
                    Toast.makeText(requireContext(), response.optString("message", "Failed to load wallet data"), Toast.LENGTH_SHORT).show();
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

    private void submitWithdrawalRequest() {
        String amountStr = etWithdrawAmount.getText().toString().trim();
        if (amountStr.isEmpty()) {
            Toast.makeText(requireContext(), "Please enter withdrawal amount in USD", Toast.LENGTH_SHORT).show();
            return;
        }

        double amount;
        try {
            amount = Double.parseDouble(amountStr);
        } catch (Exception e) {
            Toast.makeText(requireContext(), "Invalid amount format", Toast.LENGTH_SHORT).show();
            return;
        }

        if (amount < 10.0) {
            Toast.makeText(requireContext(), "Minimum withdrawal threshold is $10.00 USD", Toast.LENGTH_SHORT).show();
            return;
        }

        btnSubmitWithdrawal.setEnabled(false);
        pbWithdraw.setVisibility(View.VISIBLE);

        try {
            JSONObject body = new JSONObject();
            body.put("amount", amount);

            ApiClient.post("user/wallet.php", body, sessionManager.getToken(), new ApiClient.ApiCallback() {
                @Override
                public void onSuccess(JSONObject response) {
                    if (!isAdded()) return;
                    btnSubmitWithdrawal.setEnabled(true);
                    pbWithdraw.setVisibility(View.GONE);

                    if (response.optBoolean("success")) {
                        etWithdrawAmount.setText("");
                        Toast.makeText(requireContext(), response.optString("message", "Withdrawal submitted successfully!"), Toast.LENGTH_LONG).show();
                        loadWalletData();
                    } else {
                        Toast.makeText(requireContext(), response.optString("message", "Withdrawal failed"), Toast.LENGTH_LONG).show();
                    }
                }

                @Override
                public void onError(String errorMessage) {
                    if (!isAdded()) return;
                    btnSubmitWithdrawal.setEnabled(true);
                    pbWithdraw.setVisibility(View.GONE);
                    Toast.makeText(requireContext(), errorMessage, Toast.LENGTH_LONG).show();
                }
            });

        } catch (Exception e) {
            btnSubmitWithdrawal.setEnabled(true);
            pbWithdraw.setVisibility(View.GONE);
            Toast.makeText(requireContext(), "Error: " + e.getMessage(), Toast.LENGTH_SHORT).show();
        }
    }
}
